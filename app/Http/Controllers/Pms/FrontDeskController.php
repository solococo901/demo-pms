<?php

namespace App\Http\Controllers\Pms;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\Room;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Throwable;

class FrontDeskController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Front Desk Dashboard
    |--------------------------------------------------------------------------
    */
    public function index()
    {
        /*
        |--------------------------------------------------------------------------
        | Property
        |--------------------------------------------------------------------------
        */
        $property = Property::firstOrFail();


        /*
        |--------------------------------------------------------------------------
        | Property Timezone
        |--------------------------------------------------------------------------
        */
        $timezone =
            $property->timezone
            ?? 'Asia/Ho_Chi_Minh';


        /*
        |--------------------------------------------------------------------------
        | Today
        |--------------------------------------------------------------------------
        |
        | Quan trọng:
        | Luôn dùng timezone của Property.
        |
        */
        $today =
            CarbonImmutable::now(
                $timezone
            )->format('Y-m-d');


        /*
        |--------------------------------------------------------------------------
        | Today's Arrivals
        |--------------------------------------------------------------------------
        |
        | Booking:
        |
        | - check-in hôm nay
        | - pending / confirmed
        |
        */
        $arrivals =
            Reservation::with([
                'guest',
                'rooms.roomType',
                'rooms.room',
                'rooms.ratePlan',
            ])
                ->where(
                    'property_id',
                    $property->id
                )
                ->whereIn(
                    'status',
                    [
                        'pending',
                        'confirmed',
                    ]
                )
                ->whereHas(
                    'rooms',
                    function ($query) use ($today) {

                        $query->whereDate(
                            'check_in',
                            $today
                        );
                    }
                )
                ->orderBy('id')
                ->get();


        /*
        |--------------------------------------------------------------------------
        | In House
        |--------------------------------------------------------------------------
        |
        | Khách đang lưu trú.
        |
        */
        $inHouse =
            Reservation::with([
                'guest',
                'rooms.roomType',
                'rooms.room',
                'rooms.ratePlan',
            ])
                ->where(
                    'property_id',
                    $property->id
                )
                ->where(
                    'status',
                    'checked_in'
                )
                ->orderBy('checked_in_at')
                ->get();


        /*
        |--------------------------------------------------------------------------
        | Today's Departures
        |--------------------------------------------------------------------------
        |
        | Booking có check-out hôm nay.
        |
        | Bao gồm:
        |
        | checked_in
        | checked_out
        |
        */
        $departures =
            Reservation::with([
                'guest',
                'rooms.roomType',
                'rooms.room',
                'rooms.ratePlan',
            ])
                ->where(
                    'property_id',
                    $property->id
                )
                ->whereIn(
                    'status',
                    [
                        'checked_in',
                        'checked_out',
                    ]
                )
                ->whereHas(
                    'rooms',
                    function ($query) use ($today) {

                        $query->whereDate(
                            'check_out',
                            $today
                        );
                    }
                )
                ->orderBy('id')
                ->get();


        /*
        |--------------------------------------------------------------------------
        | Unassigned Arrivals
        |--------------------------------------------------------------------------
        |
        | Arrival hôm nay nhưng chưa assign Physical Room.
        |
        */
        $unassignedArrivals =
            $arrivals
                ->filter(
                    function ($reservation) {

                        $roomLine =
                            $reservation
                                ->rooms
                                ->first();


                        return !$roomLine
                            ||
                            !$roomLine->room_id;
                    }
                )
                ->count();


        /*
        |--------------------------------------------------------------------------
        | View
        |--------------------------------------------------------------------------
        */
        return view(
            'pms.front-desk.index',
            compact(
                'property',
                'today',
                'arrivals',
                'inHouse',
                'departures',
                'unassignedArrivals'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Check In
    |--------------------------------------------------------------------------
    */
    public function checkIn(
        Reservation $reservation
    ) {
        /*
        |--------------------------------------------------------------------------
        | Property
        |--------------------------------------------------------------------------
        */
        $property =
            Property::firstOrFail();


        /*
        |--------------------------------------------------------------------------
        | Security
        |--------------------------------------------------------------------------
        |
        | Booking phải thuộc Property hiện tại.
        |
        */
        abort_if(
            (int) $reservation->property_id
            !==
            (int) $property->id,
            404
        );


        /*
        |--------------------------------------------------------------------------
        | Valid Reservation Status
        |--------------------------------------------------------------------------
        */
        if (
            !in_array(
                $reservation->status,
                [
                    'pending',
                    'confirmed',
                ],
                true
            )
        ) {

            return back()->with(
                'error',
                'Reservation cannot be checked in from its current status.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Reservation Room
        |--------------------------------------------------------------------------
        */
        $reservationRoom =
            $reservation
                ->rooms()
                ->with([
                    'room',
                    'roomType',
                ])
                ->first();


        /*
        |--------------------------------------------------------------------------
        | Missing Room Information
        |--------------------------------------------------------------------------
        */
        if (!$reservationRoom) {

            return back()->with(
                'error',
                'Reservation does not have room information.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Physical Room Required
        |--------------------------------------------------------------------------
        */
        if (
            !$reservationRoom->room_id
        ) {

            return redirect()
                ->route(
                    'pms.reservations.show',
                    $reservation
                )
                ->with(
                    'error',
                    'Please assign a physical room before check-in.'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Property Timezone
        |--------------------------------------------------------------------------
        */
        $timezone =
            $property->timezone
            ?? 'Asia/Ho_Chi_Minh';


        /*
        |--------------------------------------------------------------------------
        | Current Property Date
        |--------------------------------------------------------------------------
        |
        | Ví dụ:
        |
        | Property = Asia/Ho_Chi_Minh
        |
        | Today:
        | 2026-09-16
        |
        */
        $today =
            CarbonImmutable::now(
                $timezone
            )->startOfDay();


        /*
        |--------------------------------------------------------------------------
        | Planned Check-in
        |--------------------------------------------------------------------------
        |
        | Quan trọng:
        |
        | Parse cùng timezone với $today.
        |
        | Đây là phần sửa lỗi bạn vừa gặp.
        |
        */
        $plannedCheckIn =
            CarbonImmutable::parse(
                $reservationRoom->check_in,
                $timezone
            )->startOfDay();


        /*
        |--------------------------------------------------------------------------
        | Planned Check-out
        |--------------------------------------------------------------------------
        */
        $plannedCheckOut =
            CarbonImmutable::parse(
                $reservationRoom->check_out,
                $timezone
            )->startOfDay();


        /*
        |--------------------------------------------------------------------------
        | Too Early
        |--------------------------------------------------------------------------
        |
        | Ví dụ:
        |
        | Today:
        | 16/09
        |
        | Check-in:
        | 17/09
        |
        | → không được check-in.
        |
        */
        if (
            $today->lt(
                $plannedCheckIn
            )
        ) {

            return back()->with(
                'error',
                'The planned check-in date is '
                . $plannedCheckIn->format('d/m/Y')
                . '.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Stay Already Ended
        |--------------------------------------------------------------------------
        |
        | Nếu hôm nay >= check-out
        | thì không check-in theo flow bình thường nữa.
        |
        */
        if (
            $today->gte(
                $plannedCheckOut
            )
        ) {

            return back()->with(
                'error',
                'This reservation has already reached or passed its check-out date.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Database Transaction
        |--------------------------------------------------------------------------
        */
        try {

            DB::transaction(
                function () use (
                    $reservation,
                    $reservationRoom,
                    $timezone
                ) {

                    /*
                    |--------------------------------------------------------------------------
                    | Lock Physical Room
                    |--------------------------------------------------------------------------
                    |
                    | Ngăn hai thao tác Front Desk
                    | cùng check-in vào một phòng.
                    |
                    */
                    $room =
                        Room::whereKey(
                            $reservationRoom->room_id
                        )
                            ->lockForUpdate()
                            ->firstOrFail();


                    /*
                    |--------------------------------------------------------------------------
                    | Room Type Verification
                    |--------------------------------------------------------------------------
                    */
                    if (
                        (int) $room->room_type_id
                        !==
                        (int) $reservationRoom->room_type_id
                    ) {

                        throw new \RuntimeException(
                            "Room {$room->room_number} does not match the reserved Room Type."
                        );
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Room Operational Status
                    |--------------------------------------------------------------------------
                    */
                    if (
                        in_array(
                            $room->status,
                            [
                                'maintenance',
                                'out_of_order',
                            ],
                            true
                        )
                    ) {

                        throw new \RuntimeException(
                            "Room {$room->room_number} is not operational."
                        );
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Already Occupied
                    |--------------------------------------------------------------------------
                    */
                    if (
                        $room->status
                        ===
                        'occupied'
                    ) {

                        throw new \RuntimeException(
                            "Room {$room->room_number} is already occupied."
                        );
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Housekeeping Check
                    |--------------------------------------------------------------------------
                    |
                    | Chỉ:
                    |
                    | clean
                    | inspected
                    |
                    | mới được check-in.
                    |
                    */
                    if (
                        !in_array(
                            $room->housekeeping_status,
                            [
                                'clean',
                                'inspected',
                            ],
                            true
                        )
                    ) {

                        throw new \RuntimeException(
                            "Room {$room->room_number} is not ready. "
                            . 'Housekeeping status: '
                            . ucfirst(
                                $room->housekeeping_status
                            )
                            . '.'
                        );
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Update Reservation
                    |--------------------------------------------------------------------------
                    */
                    $reservation->update([
                        'status' =>
                            'checked_in',

                        'checked_in_at' =>
                            CarbonImmutable::now(
                                $timezone
                            ),

                        'checked_out_at' =>
                            null,
                    ]);


                    /*
                    |--------------------------------------------------------------------------
                    | Update Physical Room
                    |--------------------------------------------------------------------------
                    |
                    | available
                    |     ↓
                    | occupied
                    |
                    */
                    $room->update([
                        'status' =>
                            'occupied',
                    ]);
                }
            );


        } catch (Throwable $exception) {

            /*
            |--------------------------------------------------------------------------
            | Friendly Error
            |--------------------------------------------------------------------------
            |
            | Không văng Laravel 500.
            |
            */
            return back()->with(
                'error',
                $exception->getMessage()
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Success
        |--------------------------------------------------------------------------
        */
        return redirect()
            ->route(
                'pms.front-desk.index'
            )
            ->with(
                'success',
                "Reservation {$reservation->code} checked in successfully."
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Check Out
    |--------------------------------------------------------------------------
    */
    public function checkOut(
        Reservation $reservation
    ) {
        /*
        |--------------------------------------------------------------------------
        | Property
        |--------------------------------------------------------------------------
        */
        $property =
            Property::firstOrFail();


        /*
        |--------------------------------------------------------------------------
        | Security
        |--------------------------------------------------------------------------
        */
        abort_if(
            (int) $reservation->property_id
            !==
            (int) $property->id,
            404
        );


        /*
        |--------------------------------------------------------------------------
        | Must Be Checked In
        |--------------------------------------------------------------------------
        */
        if (
            $reservation->status
            !==
            'checked_in'
        ) {

            return back()->with(
                'error',
                'Only an in-house reservation can be checked out.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Reservation Room
        |--------------------------------------------------------------------------
        */
        $reservationRoom =
            $reservation
                ->rooms()
                ->with([
                    'room',
                    'roomType',
                ])
                ->first();


        /*
        |--------------------------------------------------------------------------
        | Physical Room Required
        |--------------------------------------------------------------------------
        */
        if (
            !$reservationRoom
            ||
            !$reservationRoom->room_id
        ) {

            return back()->with(
                'error',
                'Reservation does not have an assigned physical room.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Timezone
        |--------------------------------------------------------------------------
        */
        $timezone =
            $property->timezone
            ?? 'Asia/Ho_Chi_Minh';


        /*
        |--------------------------------------------------------------------------
        | Transaction
        |--------------------------------------------------------------------------
        */
        try {

            DB::transaction(
                function () use (
                    $reservation,
                    $reservationRoom,
                    $timezone
                ) {

                    /*
                    |--------------------------------------------------------------------------
                    | Lock Physical Room
                    |--------------------------------------------------------------------------
                    */
                    $room =
                        Room::whereKey(
                            $reservationRoom->room_id
                        )
                            ->lockForUpdate()
                            ->firstOrFail();


                    /*
                    |--------------------------------------------------------------------------
                    | Reservation Check Out
                    |--------------------------------------------------------------------------
                    */
                    $reservation->update([
                        'status' =>
                            'checked_out',

                        'checked_out_at' =>
                            CarbonImmutable::now(
                                $timezone
                            ),
                    ]);


                    /*
                    |--------------------------------------------------------------------------
                    | Physical Room
                    |--------------------------------------------------------------------------
                    |
                    | Khách rời phòng:
                    |
                    | occupied
                    |     ↓
                    | available
                    |
                    | Nhưng Housekeeping:
                    |
                    | clean/inspected
                    |     ↓
                    | dirty
                    |
                    */
                    $room->update([
                        'status' =>
                            'available',

                        'housekeeping_status' =>
                            'dirty',
                    ]);
                }
            );


        } catch (Throwable $exception) {

            return back()->with(
                'error',
                $exception->getMessage()
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Success
        |--------------------------------------------------------------------------
        */
        return redirect()
            ->route(
                'pms.front-desk.index'
            )
            ->with(
                'success',
                "Reservation {$reservation->code} checked out successfully. Room marked Dirty."
            );
    }
}