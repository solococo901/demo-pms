<?php

namespace App\Http\Controllers\Pms;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\ReservationRoom;
use App\Models\Room;
use App\Models\RoomMove;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class RoomMoveController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Room Move Form
    |--------------------------------------------------------------------------
    */
    public function create(
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
        | Only In-House Guest
        |--------------------------------------------------------------------------
        */
        if (
            $reservation->status
            !==
            'checked_in'
        ) {

            return redirect()
                ->route(
                    'pms.reservations.show',
                    $reservation
                )
                ->with(
                    'error',
                    'Room Move is only available for checked-in reservations.'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Current Reservation Room
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


        if (
            !$reservationRoom
            ||
            !$reservationRoom->room_id
            ||
            !$reservationRoom->room
        ) {

            return redirect()
                ->route(
                    'pms.reservations.show',
                    $reservation
                )
                ->with(
                    'error',
                    'The reservation does not currently have a physical room.'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Property Date
        |--------------------------------------------------------------------------
        */
        $timezone =
            $property->timezone
            ?? 'Asia/Ho_Chi_Minh';


        $today =
            CarbonImmutable::now(
                $timezone
            )->format('Y-m-d');


        /*
        |--------------------------------------------------------------------------
        | Candidate Rooms
        |--------------------------------------------------------------------------
        |
        | Phase 1:
        |
        | Chỉ cho đổi sang Physical Room
        | cùng Room Type.
        |
        */
        $candidateRooms =
            Room::where(
                'property_id',
                $property->id
            )
                ->where(
                    'room_type_id',
                    $reservationRoom->room_type_id
                )
                ->where(
                    'id',
                    '!=',
                    $reservationRoom->room_id
                )
                ->where(
                    'status',
                    'available'
                )
                ->whereIn(
                    'housekeeping_status',
                    [
                        'clean',
                        'inspected',
                    ]
                )
                ->orderBy('floor')
                ->orderBy('room_number')
                ->get()
                ->filter(
                    function ($room) use (
                        $reservationRoom,
                        $today
                    ) {

                        /*
                        |--------------------------------------------------------------------------
                        | Conflict For Remaining Stay
                        |--------------------------------------------------------------------------
                        |
                        | Move date → planned check-out.
                        |
                        */
                        $hasConflict =
                            ReservationRoom::where(
                                'room_id',
                                $room->id
                            )
                                ->where(
                                    'id',
                                    '!=',
                                    $reservationRoom->id
                                )
                                ->where(
                                    'check_in',
                                    '<',
                                    $reservationRoom->check_out
                                )
                                ->where(
                                    'check_out',
                                    '>',
                                    $today
                                )
                                ->whereHas(
                                    'reservation',
                                    function ($query) {

                                        $query->whereNotIn(
                                            'status',
                                            [
                                                'cancelled',
                                                'no_show',
                                            ]
                                        );
                                    }
                                )
                                ->exists();


                        return !$hasConflict;
                    }
                )
                ->values();


        /*
        |--------------------------------------------------------------------------
        | Move History
        |--------------------------------------------------------------------------
        */
        $roomMoves =
            $reservation
                ->roomMoves()
                ->with([
                    'fromRoom',
                    'toRoom',
                ])
                ->orderByDesc('moved_at')
                ->orderByDesc('id')
                ->get();


        return view(
            'pms.room-moves.create',
            compact(
                'property',
                'reservation',
                'reservationRoom',
                'candidateRooms',
                'roomMoves'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Perform Room Move
    |--------------------------------------------------------------------------
    */
    public function store(
        Request $request,
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
        | Validate
        |--------------------------------------------------------------------------
        */
        $validated =
            $request->validate([
                'to_room_id' => [
                    'required',
                    'integer',
                    'exists:rooms,id',
                ],

                'reason' => [
                    'required',

                    Rule::in([
                        'guest_request',
                        'room_issue',
                        'maintenance',
                        'operational',
                        'other',
                    ]),
                ],

                'notes' => [
                    'nullable',
                    'string',
                    'max:2000',
                ],
            ]);


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

            return redirect()
                ->route(
                    'pms.reservations.show',
                    $reservation
                )
                ->with(
                    'error',
                    'Only a checked-in reservation can perform a Room Move.'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Current Room Line
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


        if (
            !$reservationRoom
            ||
            !$reservationRoom->room_id
            ||
            !$reservationRoom->room
        ) {

            return redirect()
                ->route(
                    'pms.reservations.show',
                    $reservation
                )
                ->with(
                    'error',
                    'Current physical room was not found.'
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


        $today =
            CarbonImmutable::now(
                $timezone
            )->format('Y-m-d');


        /*
        |--------------------------------------------------------------------------
        | Planned Checkout
        |--------------------------------------------------------------------------
        */
        $plannedCheckout =
            CarbonImmutable::parse(
                $reservationRoom->check_out,
                $timezone
            )->startOfDay();


        if (
            CarbonImmutable::now(
                $timezone
            )
                ->startOfDay()
                ->gte(
                    $plannedCheckout
                )
        ) {

            return back()->with(
                'error',
                'Room Move cannot be performed on or after the planned check-out date.'
            );
        }


        try {

            /*
            |--------------------------------------------------------------------------
            | Transaction
            |--------------------------------------------------------------------------
            */
            $roomMove =
                DB::transaction(
                    function () use (
                        $property,
                        $reservation,
                        $reservationRoom,
                        $validated,
                        $timezone,
                        $today
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


                        if (
                            $lockedReservation->status
                            !==
                            'checked_in'
                        ) {

                            throw new \RuntimeException(
                                'Reservation is no longer checked in.'
                            );
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | Lock Reservation Room
                        |--------------------------------------------------------------------------
                        */
                        $lockedReservationRoom =
                            ReservationRoom::whereKey(
                                $reservationRoom->id
                            )
                                ->lockForUpdate()
                                ->firstOrFail();


                        if (
                            !$lockedReservationRoom->room_id
                        ) {

                            throw new \RuntimeException(
                                'Current physical room is no longer assigned.'
                            );
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | From Room
                        |--------------------------------------------------------------------------
                        */
                        $fromRoom =
                            Room::whereKey(
                                $lockedReservationRoom->room_id
                            )
                                ->lockForUpdate()
                                ->firstOrFail();


                        /*
                        |--------------------------------------------------------------------------
                        | To Room
                        |--------------------------------------------------------------------------
                        */
                        $toRoom =
                            Room::where(
                                'property_id',
                                $property->id
                            )
                                ->whereKey(
                                    $validated['to_room_id']
                                )
                                ->lockForUpdate()
                                ->firstOrFail();


                        /*
                        |--------------------------------------------------------------------------
                        | Same Room
                        |--------------------------------------------------------------------------
                        */
                        if (
                            (int) $fromRoom->id
                            ===
                            (int) $toRoom->id
                        ) {

                            throw ValidationException::withMessages([
                                'to_room_id' =>
                                    'Please select a different physical room.',
                            ]);
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | Same Room Type
                        |--------------------------------------------------------------------------
                        |
                        | Phase 1 Room Move:
                        | không thay Room Type / Inventory.
                        |
                        */
                        if (
                            (int) $toRoom->room_type_id
                            !==
                            (int) $lockedReservationRoom->room_type_id
                        ) {

                            throw ValidationException::withMessages([
                                'to_room_id' =>
                                    'The destination room must belong to the same Room Type.',
                            ]);
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | Operational Status
                        |--------------------------------------------------------------------------
                        */
                        if (
                            $toRoom->status
                            !==
                            'available'
                        ) {

                            throw ValidationException::withMessages([
                                'to_room_id' =>
                                    "Room {$toRoom->room_number} is not available.",
                            ]);
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | Housekeeping
                        |--------------------------------------------------------------------------
                        */
                        if (
                            !in_array(
                                $toRoom->housekeeping_status,
                                [
                                    'clean',
                                    'inspected',
                                ],
                                true
                            )
                        ) {

                            throw ValidationException::withMessages([
                                'to_room_id' =>
                                    "Room {$toRoom->room_number} is not ready for a guest.",
                            ]);
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | Future / Current Conflict
                        |--------------------------------------------------------------------------
                        */
                        $hasConflict =
                            ReservationRoom::where(
                                'room_id',
                                $toRoom->id
                            )
                                ->where(
                                    'id',
                                    '!=',
                                    $lockedReservationRoom->id
                                )
                                ->where(
                                    'check_in',
                                    '<',
                                    $lockedReservationRoom->check_out
                                )
                                ->where(
                                    'check_out',
                                    '>',
                                    $today
                                )
                                ->whereHas(
                                    'reservation',
                                    function ($query) {

                                        $query->whereNotIn(
                                            'status',
                                            [
                                                'cancelled',
                                                'no_show',
                                            ]
                                        );
                                    }
                                )
                                ->exists();


                        if ($hasConflict) {

                            throw ValidationException::withMessages([
                                'to_room_id' =>
                                    "Room {$toRoom->room_number} conflicts with another reservation during the remaining stay.",
                            ]);
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | Create Audit Record
                        |--------------------------------------------------------------------------
                        */
                        $roomMove =
                            RoomMove::create([
                                'property_id' =>
                                    $property->id,

                                'reservation_id' =>
                                    $lockedReservation->id,

                                'reservation_room_id' =>
                                    $lockedReservationRoom->id,

                                'code' =>
                                    $this->generateRoomMoveCode(
                                        $property
                                    ),

                                'from_room_id' =>
                                    $fromRoom->id,

                                'to_room_id' =>
                                    $toRoom->id,

                                'from_room_number' =>
                                    $fromRoom->room_number,

                                'to_room_number' =>
                                    $toRoom->room_number,

                                'reason' =>
                                    $validated['reason'],

                                'notes' =>
                                    $validated['notes']
                                    ?? null,

                                'moved_at' =>
                                    CarbonImmutable::now(
                                        $timezone
                                    ),
                            ]);


                        /*
                        |--------------------------------------------------------------------------
                        | Reservation → New Room
                        |--------------------------------------------------------------------------
                        */
                        $lockedReservationRoom->update([
                            'room_id' =>
                                $toRoom->id,
                        ]);


                        /*
                        |--------------------------------------------------------------------------
                        | Old Room
                        |--------------------------------------------------------------------------
                        |
                        | Khách vừa rời phòng:
                        |
                        | Occupied
                        |   ↓
                        | Available
                        |
                        | Housekeeping:
                        |   ↓
                        | Dirty
                        |
                        */
                        $fromRoom->update([
                            'status' =>
                                'available',

                            'housekeeping_status' =>
                                'dirty',
                        ]);


                        /*
                        |--------------------------------------------------------------------------
                        | New Room
                        |--------------------------------------------------------------------------
                        */
                        $toRoom->update([
                            'status' =>
                                'occupied',
                        ]);


                        /*
                        |--------------------------------------------------------------------------
                        | IMPORTANT
                        |--------------------------------------------------------------------------
                        |
                        | KHÔNG thay đổi:
                        |
                        | - Inventory
                        | - Reservation Room Type
                        | - Rate Plan
                        | - Channex Availability
                        |
                        */
                        return $roomMove;
                    }
                );

        } catch (ValidationException $exception) {

            throw $exception;

        } catch (Throwable $exception) {

            return back()
                ->withInput()
                ->with(
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
                'pms.reservations.show',
                $reservation
            )
            ->with(
                'success',
                "Room Move {$roomMove->code}: "
                . "{$roomMove->from_room_number} → {$roomMove->to_room_number} completed successfully."
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Generate Room Move Code
    |--------------------------------------------------------------------------
    */
    private function generateRoomMoveCode(
        Property $property
    ): string {

        $latestMove =
            RoomMove::where(
                'property_id',
                $property->id
            )
                ->where(
                    'code',
                    'like',
                    'RMV_%'
                )
                ->orderByDesc('id')
                ->first();


        $nextNumber =
            1;


        if ($latestMove) {

            $number =
                (int) str_replace(
                    'RMV_',
                    '',
                    $latestMove->code
                );


            $nextNumber =
                $number + 1;
        }


        return 'RMV_'
            . str_pad(
                (string) $nextNumber,
                6,
                '0',
                STR_PAD_LEFT
            );
    }
}