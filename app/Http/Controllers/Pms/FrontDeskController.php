<?php

namespace App\Http\Controllers\Pms;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\ReservationRoom;
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
        $property =
            Property::firstOrFail();


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
        | Khách dự kiến nhận phòng hôm nay.
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
        | Khách dự kiến trả phòng hôm nay.
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
        | Khách đến hôm nay nhưng chưa gán phòng vật lý.
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
        | Outstanding In-House
        |--------------------------------------------------------------------------
        |
        | Số booking đang ở khách sạn nhưng còn công nợ.
        |
        */
        $outstandingInHouse =
            $inHouse
                ->filter(
                    function ($reservation) {

                        $balance =
                            max(
                                0,
                                (float) $reservation->total_amount
                                -
                                (float) $reservation->paid_amount
                            );


                        return $balance > 0;
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
                'unassignedArrivals',
                'outstandingInHouse'
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
        */
        abort_if(
            (int) $reservation->property_id
            !==
            (int) $property->id,
            404
        );


        /*
        |--------------------------------------------------------------------------
        | Reservation Status
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
        | Stay Information Required
        |--------------------------------------------------------------------------
        */
        if (!$reservationRoom) {

            return back()->with(
                'error',
                'Reservation does not have stay information.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Physical Room Required
        |--------------------------------------------------------------------------
        */
        if (!$reservationRoom->room_id) {

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
        | Timezone
        |--------------------------------------------------------------------------
        */
        $timezone =
            $property->timezone
            ?? 'Asia/Ho_Chi_Minh';


        /*
        |--------------------------------------------------------------------------
        | Dates
        |--------------------------------------------------------------------------
        */
        $today =
            CarbonImmutable::now(
                $timezone
            )->startOfDay();


        $plannedCheckIn =
            CarbonImmutable::parse(
                $reservationRoom->check_in,
                $timezone
            )->startOfDay();


        $plannedCheckOut =
            CarbonImmutable::parse(
                $reservationRoom->check_out,
                $timezone
            )->startOfDay();


        /*
        |--------------------------------------------------------------------------
        | Early Check-In Validation
        |--------------------------------------------------------------------------
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
        */
        if (
            $today->gte(
                $plannedCheckOut
            )
        ) {

            return back()->with(
                'error',
                'This reservation has already reached or passed its planned check-out date.'
            );
        }


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
                    | Room Type
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
                    | Operational Status
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
                    | Another Checked-In Guest?
                    |--------------------------------------------------------------------------
                    |
                    | Kiểm tra thêm từ ReservationRoom,
                    | không chỉ tin vào rooms.status.
                    |
                    */
                    $occupiedByAnother =
                        ReservationRoom::where(
                            'room_id',
                            $room->id
                        )
                            ->where(
                                'id',
                                '!=',
                                $reservationRoom->id
                            )
                            ->whereHas(
                                'reservation',
                                function ($query) {

                                    $query->where(
                                        'status',
                                        'checked_in'
                                    );
                                }
                            )
                            ->exists();


                    if ($occupiedByAnother) {

                        throw new \RuntimeException(
                            "Room {$room->room_number} currently has another checked-in guest."
                        );
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Occupied Status
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
                    | Housekeeping
                    |--------------------------------------------------------------------------
                    |
                    | Chỉ Clean / Inspected mới nhận khách.
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
                    | Reservation → Checked In
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
                    | Room → Occupied
                    |--------------------------------------------------------------------------
                    */
                    $room->update([
                        'status' =>
                            'occupied',
                    ]);
                }
            );

        } catch (Throwable $exception) {

            return back()->with(
                'error',
                $exception->getMessage()
            );
        }


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
        | Refresh Reservation
        |--------------------------------------------------------------------------
        |
        | Lấy total_amount / paid_amount mới nhất.
        |
        */
        $reservation->refresh();


        /*
        |--------------------------------------------------------------------------
        | Total
        |--------------------------------------------------------------------------
        */
        $totalAmount =
            (float) $reservation
                ->total_amount;


        /*
        |--------------------------------------------------------------------------
        | Net Paid
        |--------------------------------------------------------------------------
        |
        | paid_amount hiện được dùng là:
        |
        | Gross Payments
        | -
        | Completed Refunds
        |
        */
        $netPaid =
            (float) $reservation
                ->paid_amount;


        /*
        |--------------------------------------------------------------------------
        | Outstanding Balance
        |--------------------------------------------------------------------------
        */
        $balance =
            max(
                0,
                $totalAmount
                -
                $netPaid
            );


        /*
        |--------------------------------------------------------------------------
        | CHECKOUT PAYMENT VALIDATION
        |--------------------------------------------------------------------------
        |
        | Phase 1:
        |
        | Không cho checkout nếu còn công nợ.
        |
        */
        if (
            $balance
            >
            0
        ) {

            return redirect()
                ->route(
                    'pms.reservations.show',
                    $reservation
                )
                ->with(
                    'error',
                    'Cannot check out yet. Outstanding balance: '
                    . number_format(
                        $balance,
                        0,
                        ',',
                        '.'
                    )
                    . ' ₫. Please complete payment before check-out.'
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
                    | Lock Reservation
                    |--------------------------------------------------------------------------
                    */
                    $lockedReservation =
                        Reservation::whereKey(
                            $reservation->id
                        )
                            ->lockForUpdate()
                            ->firstOrFail();


                    /*
                    |--------------------------------------------------------------------------
                    | Re-check Balance Inside Transaction
                    |--------------------------------------------------------------------------
                    |
                    | Tránh payment/folio thay đổi ngay lúc checkout.
                    |
                    */
                    $total =
                        (float) $lockedReservation
                            ->total_amount;


                    $paid =
                        (float) $lockedReservation
                            ->paid_amount;


                    $currentBalance =
                        max(
                            0,
                            $total
                            -
                            $paid
                        );


                    if (
                        $currentBalance
                        >
                        0
                    ) {

                        throw new \RuntimeException(
                            'Outstanding balance is '
                            . number_format(
                                $currentBalance,
                                0,
                                ',',
                                '.'
                            )
                            . ' ₫. Complete payment before check-out.'
                        );
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Lock Room
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
                    | Reservation → Checked Out
                    |--------------------------------------------------------------------------
                    */
                    $lockedReservation->update([
                        'status' =>
                            'checked_out',

                        'checked_out_at' =>
                            CarbonImmutable::now(
                                $timezone
                            ),
                    ]);


                    /*
                    |--------------------------------------------------------------------------
                    | Room
                    |--------------------------------------------------------------------------
                    |
                    | occupied → available
                    |
                    | housekeeping → dirty
                    |
                    */
                    $room->update([
                        'status' =>
                            'available',

                        'housekeeping_status' =>
                            'dirty',
                    ]);


                    /*
                    |--------------------------------------------------------------------------
                    | IMPORTANT
                    |--------------------------------------------------------------------------
                    |
                    | Không thay đổi:
                    |
                    | Inventory
                    | Channex Availability
                    |
                    | vì reservation đã giữ Inventory từ lúc booking.
                    |
                    */
                }
            );

        } catch (Throwable $exception) {

            return back()->with(
                'error',
                $exception->getMessage()
            );
        }


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