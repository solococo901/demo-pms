<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\Property;
use App\Models\RoomType;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;
use Throwable;

class ReservationInventoryService
{
    /*
    |--------------------------------------------------------------------------
    | Reserve Inventory
    |--------------------------------------------------------------------------
    |
    | Ví dụ:
    |
    | Booking:
    | 17/09 → 19/09
    |
    | Giảm:
    | 17/09
    | 18/09
    |
    | Không giảm:
    | 19/09
    |
    */
    public function reserve(
        Property $property,
        RoomType $roomType,
        string $checkIn,
        string $checkOut
    ): array {

        $inventoryIds = [];

        $currentDate =
            CarbonImmutable::parse(
                $checkIn
            )->startOfDay();

        $endDate =
            CarbonImmutable::parse(
                $checkOut
            )->startOfDay();


        /*
        |--------------------------------------------------------------------------
        | Loop nights
        |--------------------------------------------------------------------------
        */
        while (
            $currentDate->lt(
                $endDate
            )
        ) {

            $date =
                $currentDate->format(
                    'Y-m-d'
                );


            /*
            |--------------------------------------------------------------------------
            | Get Inventory
            |--------------------------------------------------------------------------
            |
            | lockForUpdate:
            | tránh 2 booking cùng lúc cùng đọc một availability.
            |
            */
            $inventory =
                Inventory::where(
                    'property_id',
                    $property->id
                )
                    ->where(
                        'room_type_id',
                        $roomType->id
                    )
                    ->whereDate(
                        'date',
                        $date
                    )
                    ->lockForUpdate()
                    ->first();


            /*
            |--------------------------------------------------------------------------
            | Create Inventory if missing
            |--------------------------------------------------------------------------
            */
            if (!$inventory) {

                $inventory =
                    Inventory::create([
                        'property_id' =>
                            $property->id,

                        'room_type_id' =>
                            $roomType->id,

                        'date' =>
                            $date,

                        'availability' =>
                            $roomType->total_rooms,

                        'sync_status' =>
                            'pending',

                        'synced_at' =>
                            null,
                    ]);
            }


            /*
            |--------------------------------------------------------------------------
            | Sold Out
            |--------------------------------------------------------------------------
            */
            if (
                (int) $inventory->availability
                <=
                0
            ) {

                throw ValidationException::withMessages([
                    'room_type_id' =>
                        "{$roomType->name} không còn phòng ngày "
                        . $currentDate->format('d/m/Y')
                        . '.',
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | Reduce Availability
            |--------------------------------------------------------------------------
            */
            $inventory->update([
                'availability' =>
                    (int) $inventory->availability
                    - 1,

                'sync_status' =>
                    'pending',

                'synced_at' =>
                    null,
            ]);


            $inventoryIds[] =
                $inventory->id;


            /*
            |--------------------------------------------------------------------------
            | Next Night
            |--------------------------------------------------------------------------
            */
            $currentDate =
                $currentDate->addDay();
        }


        return array_values(
            array_unique(
                $inventoryIds
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Release Inventory
    |--------------------------------------------------------------------------
    |
    | Dùng khi:
    |
    | - Cancel Reservation
    | - No Show
    | - đổi ngày
    | - đổi Room Type
    |
    */
    public function release(
        Property $property,
        RoomType $roomType,
        string $checkIn,
        string $checkOut
    ): array {

        $inventoryIds = [];

        $currentDate =
            CarbonImmutable::parse(
                $checkIn
            )->startOfDay();

        $endDate =
            CarbonImmutable::parse(
                $checkOut
            )->startOfDay();


        while (
            $currentDate->lt(
                $endDate
            )
        ) {

            $date =
                $currentDate->format(
                    'Y-m-d'
                );


            $inventory =
                Inventory::where(
                    'property_id',
                    $property->id
                )
                    ->where(
                        'room_type_id',
                        $roomType->id
                    )
                    ->whereDate(
                        'date',
                        $date
                    )
                    ->lockForUpdate()
                    ->first();


            /*
            |--------------------------------------------------------------------------
            | Missing Inventory
            |--------------------------------------------------------------------------
            |
            | Nếu vì dữ liệu cũ mà record chưa tồn tại,
            | tạo lại ở full capacity.
            |
            */
            if (!$inventory) {

                $inventory =
                    Inventory::create([
                        'property_id' =>
                            $property->id,

                        'room_type_id' =>
                            $roomType->id,

                        'date' =>
                            $date,

                        'availability' =>
                            $roomType->total_rooms,

                        'sync_status' =>
                            'pending',

                        'synced_at' =>
                            null,
                    ]);


                $inventoryIds[] =
                    $inventory->id;


                $currentDate =
                    $currentDate->addDay();


                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | Increase Availability
            |--------------------------------------------------------------------------
            |
            | Không được vượt total_rooms.
            |
            */
            $newAvailability =
                min(
                    (int) $roomType->total_rooms,
                    (int) $inventory->availability
                    + 1
                );


            $inventory->update([
                'availability' =>
                    $newAvailability,

                'sync_status' =>
                    'pending',

                'synced_at' =>
                    null,
            ]);


            $inventoryIds[] =
                $inventory->id;


            $currentDate =
                $currentDate->addDay();
        }


        return array_values(
            array_unique(
                $inventoryIds
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Sync Inventory → Channex
    |--------------------------------------------------------------------------
    |
    | Chỉ những Room Type đã map Channex
    | mới được gửi.
    |
    */
    public function syncToChannex(
        Property $property,
        array $inventoryIds,
        ChannexService $channex
    ): array {

        /*
        |--------------------------------------------------------------------------
        | Nothing Changed
        |--------------------------------------------------------------------------
        */
        if (empty($inventoryIds)) {

            return [
                'status' =>
                    'nothing_to_sync',

                'message' =>
                    'Không có Inventory thay đổi.',
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Property Not Mapped
        |--------------------------------------------------------------------------
        */
        if (
            !$property
                ->channex_property_id
        ) {

            return [
                'status' =>
                    'not_mapped',

                'message' =>
                    'Inventory đã cập nhật trong PMS nhưng Property chưa map Channex.',
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Load Inventories
        |--------------------------------------------------------------------------
        */
        $inventories =
            Inventory::with(
                'roomType'
            )
                ->whereIn(
                    'id',
                    array_values(
                        array_unique(
                            $inventoryIds
                        )
                    )
                )
                ->get();


        $values = [];

        $syncInventoryIds = [];


        foreach (
            $inventories
            as
            $inventory
        ) {

            $roomType =
                $inventory->roomType;


            /*
            |--------------------------------------------------------------------------
            | Room Type Not Mapped
            |--------------------------------------------------------------------------
            */
            if (
                !$roomType
                ||
                !$roomType
                    ->channex_room_type_id
            ) {
                continue;
            }


            $date =
                CarbonImmutable::parse(
                    $inventory->date
                )->format('Y-m-d');


            /*
            |--------------------------------------------------------------------------
            | Channex Availability Payload
            |--------------------------------------------------------------------------
            */
            $values[] = [
                'property_id' =>
                    $property
                        ->channex_property_id,

                'room_type_id' =>
                    $roomType
                        ->channex_room_type_id,

                'date' =>
                    $date,

                'availability' =>
                    (int) $inventory
                        ->availability,
            ];


            $syncInventoryIds[] =
                $inventory->id;
        }


        /*
        |--------------------------------------------------------------------------
        | Nothing Mapped
        |--------------------------------------------------------------------------
        */
        if (
            empty($values)
            ||
            empty($syncInventoryIds)
        ) {

            return [
                'status' =>
                    'room_types_not_mapped',

                'message' =>
                    'Inventory đã cập nhật trong PMS nhưng không có Room Type phù hợp đã map Channex.',
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Send Channex
        |--------------------------------------------------------------------------
        */
        try {

            $response =
                $channex
                    ->updateAvailability(
                        $values
                    );


            /*
            |--------------------------------------------------------------------------
            | Warnings
            |--------------------------------------------------------------------------
            */
            $warnings =
                $response['meta']['warnings']
                ?? [];


            if (
                !empty($warnings)
            ) {

                Inventory::whereIn(
                    'id',
                    $syncInventoryIds
                )->update([
                            'sync_status' =>
                                'warning',

                            'synced_at' =>
                                null,
                        ]);


                return [
                    'status' =>
                        'warning',

                    'message' =>
                        'Inventory đã cập nhật nhưng Channex trả về warning.',

                    'warnings' =>
                        $warnings,
                ];
            }


            /*
            |--------------------------------------------------------------------------
            | Success
            |--------------------------------------------------------------------------
            */
            Inventory::whereIn(
                'id',
                $syncInventoryIds
            )->update([
                        'sync_status' =>
                            'synced',

                        'synced_at' =>
                            now(),
                    ]);


            return [
                'status' =>
                    'synced',

                'message' =>
                    'Inventory đã đồng bộ Channex thành công.',

                'count' =>
                    count(
                        $syncInventoryIds
                    ),
            ];


        } catch (Throwable $exception) {

            /*
            |--------------------------------------------------------------------------
            | Failed
            |--------------------------------------------------------------------------
            */
            Inventory::whereIn(
                'id',
                $syncInventoryIds
            )->update([
                        'sync_status' =>
                            'failed',

                        'synced_at' =>
                            null,
                    ]);


            return [
                'status' =>
                    'failed',

                'message' =>
                    'Inventory đã lưu trong PMS nhưng sync Channex thất bại: '
                    . $exception->getMessage(),
            ];
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Reservation Holds Inventory?
    |--------------------------------------------------------------------------
    |
    | Các trạng thái này giữ inventory:
    |
    | pending
    | confirmed
    | checked_in
    |
    */
    public function holdsInventory(
        string $status
    ): bool {

        return in_array(
            $status,
            [
                'pending',
                'confirmed',
                'checked_in',
                'checked_out',
            ],
            true
        );
    }
}