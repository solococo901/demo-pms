<?php

namespace App\Http\Controllers\Pms;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\ReservationRoom;
use App\Models\Room;
use App\Services\ChannexService;
use App\Services\ReservationInventoryService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class NoShowController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Mark Reservation As No-show
    |--------------------------------------------------------------------------
    */
    public function store(
        Request $request,
        Reservation $reservation,
        ReservationInventoryService $inventoryService,
        ChannexService $channex
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
                'reason' => [
                    'nullable',
                    'string',
                    'max:255',
                ],
            ]);


        /*
        |--------------------------------------------------------------------------
        | Allowed Status
        |--------------------------------------------------------------------------
        |
        | Chỉ booking chưa Check-in mới được No-show.
        |
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
                'Only Pending or Confirmed reservations can be marked as No-show.'
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


        if (!$reservationRoom) {

            return back()->with(
                'error',
                'Reservation stay information was not found.'
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
            )->startOfDay();


        $plannedCheckIn =
            CarbonImmutable::parse(
                $reservationRoom->check_in,
                $timezone
            )->startOfDay();


        /*
        |--------------------------------------------------------------------------
        | Cannot No-show Future Booking
        |--------------------------------------------------------------------------
        |
        | Ví dụ:
        |
        | Hôm nay:   18/09
        | Check-in:  20/09
        |
        | → chưa thể đánh dấu No-show.
        |
        */
        if (
            $today->lt(
                $plannedCheckIn
            )
        ) {

            return back()->with(
                'error',
                'This reservation cannot be marked as No-show before '
                . $plannedCheckIn->format('d/m/Y')
                . '.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Inventory
        |--------------------------------------------------------------------------
        */
        $shouldReleaseInventory =
            $reservationRoom->roomType
            &&
            $inventoryService->holdsInventory(
                $reservation->status
            );


        try {

            /*
            |--------------------------------------------------------------------------
            | Database Transaction
            |--------------------------------------------------------------------------
            */
            $inventoryIds =
                DB::transaction(
                    function () use (
                        $property,
                        $reservation,
                        $reservationRoom,
                        $validated,
                        $timezone,
                        $inventoryService,
                        $shouldReleaseInventory
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
                        | Re-check Status
                        |--------------------------------------------------------------------------
                        */
                        if (
                            !in_array(
                                $lockedReservation->status,
                                [
                                    'pending',
                                    'confirmed',
                                ],
                                true
                            )
                        ) {

                            throw new \RuntimeException(
                                'Reservation status has changed. Please refresh the page.'
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


                        /*
                        |--------------------------------------------------------------------------
                        | Physical Room Validation
                        |--------------------------------------------------------------------------
                        */
                        if (
                            $lockedReservationRoom->room_id
                        ) {

                            $physicalRoom =
                                Room::whereKey(
                                    $lockedReservationRoom->room_id
                                )
                                    ->lockForUpdate()
                                    ->first();


                            /*
                            |--------------------------------------------------------------------------
                            | Safety Check
                            |--------------------------------------------------------------------------
                            |
                            | Pending/Confirmed booking không nên có Room Occupied.
                            |
                            */
                            if (
                                $physicalRoom
                                &&
                                $physicalRoom->status
                                ===
                                'occupied'
                            ) {

                                throw new \RuntimeException(
                                    "Room {$physicalRoom->room_number} is currently Occupied. "
                                    . 'Resolve the room status before marking this reservation as No-show.'
                                );
                            }
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | Release Inventory
                        |--------------------------------------------------------------------------
                        */
                        $inventoryIds = [];


                        if (
                            $shouldReleaseInventory
                            &&
                            $lockedReservationRoom->roomType
                        ) {

                            $inventoryIds =
                                $inventoryService->release(
                                    $property,
                                    $lockedReservationRoom->roomType,
                                    $lockedReservationRoom->check_in,
                                    $lockedReservationRoom->check_out
                                );
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | Unassign Physical Room
                        |--------------------------------------------------------------------------
                        |
                        | Reservation không còn giữ phòng vật lý.
                        |
                        */
                        $lockedReservationRoom->update([
                            'room_id' =>
                                null,
                        ]);


                        /*
                        |--------------------------------------------------------------------------
                        | Mark No-show
                        |--------------------------------------------------------------------------
                        */
                        $lockedReservation->update([
                            'status' =>
                                'no_show',

                            'no_show_at' =>
                                CarbonImmutable::now(
                                    $timezone
                                ),

                            'no_show_reason' =>
                                $validated['reason']
                                ?? null,
                        ]);


                        return $inventoryIds;
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
        | Only Sync Today / Future Inventory
        |--------------------------------------------------------------------------
        |
        | Không cần gửi Availability của ngày quá khứ lên Channel.
        |
        */
        $syncInventoryIds =
            Inventory::whereIn(
                'id',
                $inventoryIds
            )
                ->whereDate(
                    'date',
                    '>=',
                    $today->format('Y-m-d')
                )
                ->pluck('id')
                ->all();


        /*
        |--------------------------------------------------------------------------
        | Sync Availability → Channex
        |--------------------------------------------------------------------------
        */
        $syncResult =
            $inventoryService
                ->syncToChannex(
                    $property,
                    $syncInventoryIds,
                    $channex
                );


        /*
        |--------------------------------------------------------------------------
        | Channex Warning / Failure
        |--------------------------------------------------------------------------
        |
        | No-show vẫn được ghi nhận trong PMS.
        |
        | Chỉ cảnh báo Channel Sync riêng.
        |
        */
        if (
            in_array(
                $syncResult['status'] ?? '',
                [
                    'failed',
                    'warning',
                ],
                true
            )
        ) {

            return redirect()
                ->route(
                    'pms.reservations.show',
                    $reservation
                )
                ->with(
                    'success',
                    "Reservation {$reservation->code} marked as No-show."
                )
                ->with(
                    'error',
                    $syncResult['message']
                    ?? 'Availability could not be fully synchronized to Channex.'
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
                "Reservation {$reservation->code} marked as No-show. Inventory released."
            );
    }
}