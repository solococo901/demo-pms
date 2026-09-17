<?php

namespace App\Http\Controllers\Pms;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\Property;
use App\Models\RoomType;
use App\Services\ChannexService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class InventoryController extends Controller
{
    /**
     * Inventory Calendar
     */
    public function index(Request $request)
    {
        $property = Property::firstOrFail();

        /*
        |--------------------------------------------------------------------------
        | Date Range
        |--------------------------------------------------------------------------
        |
        | Demo hiển thị 7 ngày.
        |
        */
        $startDate = $request->filled('start')
            ? CarbonImmutable::parse($request->start)->startOfDay()
            : CarbonImmutable::today();

        $dates = collect(
            range(0, 6)
        )->map(
            fn ($day) => $startDate->addDays($day)
        );


        /*
        |--------------------------------------------------------------------------
        | Room Types
        |--------------------------------------------------------------------------
        */
        $roomTypes = RoomType::where(
            'property_id',
            $property->id
        )
            ->where('status', 'active')
            ->orderBy('name')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Create Inventory if missing
        |--------------------------------------------------------------------------
        |
        | Không dùng firstOrCreate(date)
        | vì SQLite/Eloquent có thể lưu DATE thành:
        |
        | 2026-09-16 00:00:00
        |
        | trong khi query lại bằng:
        |
        | 2026-09-16
        |
        | gây UNIQUE constraint.
        |
        */
        foreach ($roomTypes as $roomType) {

            foreach ($dates as $date) {

                $dateString =
                    $date->format('Y-m-d');

                $inventory = Inventory::where(
                    'room_type_id',
                    $roomType->id
                )
                    ->whereDate(
                        'date',
                        $dateString
                    )
                    ->first();


                /*
                 * Chỉ tạo khi thực sự chưa tồn tại.
                 */
                if (!$inventory) {

                    Inventory::create([
                        'property_id' =>
                            $property->id,

                        'room_type_id' =>
                            $roomType->id,

                        'date' =>
                            $dateString,

                        'availability' =>
                            $roomType->total_rooms,

                        'sync_status' =>
                            'pending',
                    ]);
                }
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Get Inventory
        |--------------------------------------------------------------------------
        */
        $inventories = Inventory::where(
            'property_id',
            $property->id
        )
            ->whereDate(
                'date',
                '>=',
                $dates->first()->format('Y-m-d')
            )
            ->whereDate(
                'date',
                '<=',
                $dates->last()->format('Y-m-d')
            )
            ->get()
            ->keyBy(function ($item) {

                $date = CarbonImmutable::parse(
                    $item->date
                )->format('Y-m-d');

                return $item->room_type_id
                    . '_'
                    . $date;
            });


        return view(
            'pms.inventory.index',
            compact(
                'property',
                'roomTypes',
                'dates',
                'inventories',
                'startDate'
            )
        );
    }


    /**
     * Save Inventory + Sync Channex
     */
    public function update(
        Request $request,
        ChannexService $channex
    ) {
        $property = Property::firstOrFail();


        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */
        $request->validate([
            'start_date' => [
                'required',
                'date',
            ],

            'availability' => [
                'required',
                'array',
            ],

            'availability.*' => [
                'required',
                'array',
            ],

            'availability.*.*' => [
                'required',
                'integer',
                'min:0',
            ],
        ]);


        /*
        |--------------------------------------------------------------------------
        | Get Room Types
        |--------------------------------------------------------------------------
        */
        $roomTypes = RoomType::where(
            'property_id',
            $property->id
        )
            ->get()
            ->keyBy('id');


        $channexValues = [];

        $inventoryIds = [];


        /*
        |--------------------------------------------------------------------------
        | Save Local Inventory
        |--------------------------------------------------------------------------
        */
        DB::transaction(function () use (
            $request,
            $property,
            $roomTypes,
            &$channexValues,
            &$inventoryIds
        ) {

            foreach (
                $request->availability
                as $roomTypeId => $dateValues
            ) {

                /*
                 * Check Room Type belongs
                 * to this Property.
                 */
                $roomType = $roomTypes->get(
                    (int) $roomTypeId
                );

                if (!$roomType) {
                    continue;
                }


                foreach (
                    $dateValues
                    as $date => $availability
                ) {

                    /*
                     * Normalize Date
                     */
                    $date = CarbonImmutable::parse(
                        $date
                    )->format('Y-m-d');


                    /*
                     * Normalize Availability
                     */
                    $availability =
                        (int) $availability;


                    /*
                    |--------------------------------------------------------------------------
                    | Validation against physical inventory
                    |--------------------------------------------------------------------------
                    |
                    | Demo PMS:
                    |
                    | Nếu Deluxe có 15 phòng vật lý
                    | không cho nhập availability = 20.
                    |
                    */
                    if (
                        $availability
                        >
                        $roomType->total_rooms
                    ) {

                        abort(
                            422,
                            "Availability của {$roomType->name} không được lớn hơn {$roomType->total_rooms}."
                        );
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Find existing Inventory
                    |--------------------------------------------------------------------------
                    |
                    | QUAN TRỌNG:
                    |
                    | whereDate() giải quyết lỗi:
                    |
                    | 2026-09-16
                    |
                    | vs
                    |
                    | 2026-09-16 00:00:00
                    |
                    */
                    $inventory = Inventory::where(
                        'room_type_id',
                        $roomType->id
                    )
                        ->whereDate(
                            'date',
                            $date
                        )
                        ->first();


                    /*
                    |--------------------------------------------------------------------------
                    | Update existing
                    |--------------------------------------------------------------------------
                    */
                    if ($inventory) {

                        $inventory->update([
                            'property_id' =>
                                $property->id,

                            'availability' =>
                                $availability,

                            'sync_status' =>
                                'pending',

                            'synced_at' =>
                                null,
                        ]);

                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Create new
                    |--------------------------------------------------------------------------
                    */
                    else {

                        $inventory = Inventory::create([
                            'property_id' =>
                                $property->id,

                            'room_type_id' =>
                                $roomType->id,

                            'date' =>
                                $date,

                            'availability' =>
                                $availability,

                            'sync_status' =>
                                'pending',

                            'synced_at' =>
                                null,
                        ]);
                    }


                    $inventoryIds[] =
                        $inventory->id;


                    /*
                    |--------------------------------------------------------------------------
                    | Build Channex Payload
                    |--------------------------------------------------------------------------
                    |
                    | Chỉ sync nếu:
                    |
                    | Property mapped
                    | +
                    | Room Type mapped
                    |
                    */
                    if (
                        $property->channex_property_id
                        &&
                        $roomType
                            ->channex_room_type_id
                    ) {

                        $channexValues[] = [

                            'property_id' =>
                                $property
                                    ->channex_property_id,

                            'room_type_id' =>
                                $roomType
                                    ->channex_room_type_id,

                            'date' =>
                                $date,

                            'availability' =>
                                $availability,
                        ];
                    }
                }
            }
        });


        /*
        |--------------------------------------------------------------------------
        | Remove duplicate Inventory IDs
        |--------------------------------------------------------------------------
        */
        $inventoryIds = array_values(
            array_unique(
                $inventoryIds
            )
        );


        /*
        |--------------------------------------------------------------------------
        | Property not mapped
        |--------------------------------------------------------------------------
        */
        if (
            !$property->channex_property_id
        ) {

            return redirect()
                ->route(
                    'pms.inventory.index',
                    [
                        'start' =>
                            $request->start_date
                    ]
                )
                ->with(
                    'success',
                    'Inventory đã được lưu trong PMS. Property chưa được map với Channex.'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | No mapped Room Types
        |--------------------------------------------------------------------------
        */
        if (empty($channexValues)) {

            return redirect()
                ->route(
                    'pms.inventory.index',
                    [
                        'start' =>
                            $request->start_date
                    ]
                )
                ->with(
                    'error',
                    'Inventory đã được lưu, nhưng chưa có Room Type nào được map với Channex.'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Sync Channex
        |--------------------------------------------------------------------------
        */
        try {

            $response =
                $channex->updateAvailability(
                    $channexValues
                );


            /*
            |--------------------------------------------------------------------------
            | Channex Warnings
            |--------------------------------------------------------------------------
            */
            $warnings =
                $response['meta']['warnings']
                ?? [];


            if (!empty($warnings)) {

                Inventory::whereIn(
                    'id',
                    $inventoryIds
                )->update([
                    'sync_status' =>
                        'warning',
                ]);


                return redirect()
                    ->route(
                        'pms.inventory.index',
                        [
                            'start' =>
                                $request->start_date
                        ]
                    )
                    ->with(
                        'error',
                        'Inventory đã lưu nhưng Channex trả về warning.'
                    );
            }


            /*
            |--------------------------------------------------------------------------
            | Success
            |--------------------------------------------------------------------------
            */
            Inventory::whereIn(
                'id',
                $inventoryIds
            )->update([
                'sync_status' =>
                    'synced',

                'synced_at' =>
                    now(),
            ]);


            return redirect()
                ->route(
                    'pms.inventory.index',
                    [
                        'start' =>
                            $request->start_date
                    ]
                )
                ->with(
                    'success',
                    'Inventory đã lưu và đồng bộ Channex thành công.'
                );

        } catch (Throwable $exception) {


            /*
            |--------------------------------------------------------------------------
            | Failed
            |--------------------------------------------------------------------------
            */
            Inventory::whereIn(
                'id',
                $inventoryIds
            )->update([
                'sync_status' =>
                    'failed',
            ]);


            return redirect()
                ->route(
                    'pms.inventory.index',
                    [
                        'start' =>
                            $request->start_date
                    ]
                )
                ->with(
                    'error',
                    'Inventory đã lưu trong PMS nhưng Channex sync thất bại: '
                    . $exception->getMessage()
                );
        }
    }
}