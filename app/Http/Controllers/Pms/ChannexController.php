<?php

namespace App\Http\Controllers\Pms;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\RoomType;
use App\Models\RatePlan;
use App\Services\ChannexService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class ChannexController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Channex Page
    |--------------------------------------------------------------------------
    |
    | Load:
    | - PMS Property
    | - PMS Room Types
    | - PMS Rate Plans
    | - Channex Properties
    | - Channex Room Types
    | - Channex Rate Plans
    |
    */
    public function index(
        ChannexService $channex
    ) {
        /*
        |--------------------------------------------------------------------------
        | PMS Property
        |--------------------------------------------------------------------------
        */
        $property = Property::firstOrFail();


        /*
        |--------------------------------------------------------------------------
        | PMS Room Types
        |--------------------------------------------------------------------------
        */
        $roomTypes = RoomType::where(
            'property_id',
            $property->id
        )
            ->orderBy('name')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | PMS Rate Plans
        |--------------------------------------------------------------------------
        */
        $ratePlans = RatePlan::with('roomType')
            ->where(
                'property_id',
                $property->id
            )
            ->orderBy('room_type_id')
            ->orderBy('name')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Initial Channex State
        |--------------------------------------------------------------------------
        */
        $connected = false;

        $error = null;

        $channexProperties = [];

        $channexRoomTypes = [];

        $channexRatePlans = [];


        try {

            /*
            |--------------------------------------------------------------------------
            | Test Channex API Connection
            |--------------------------------------------------------------------------
            */
            $response = $channex->properties();

            $channexProperties =
                $response['data'] ?? [];

            $connected = true;


            /*
            |--------------------------------------------------------------------------
            | Property already mapped
            |--------------------------------------------------------------------------
            |
            | Nếu Property PMS đã map với Channex,
            | load thêm Room Types và Rate Plans.
            |
            */
            if ($property->channex_property_id) {

                /*
                |--------------------------------------------------------------------------
                | Channex Room Types
                |--------------------------------------------------------------------------
                */
                $roomResponse =
                    $channex->roomTypes(
                        $property->channex_property_id
                    );

                $channexRoomTypes =
                    $roomResponse['data'] ?? [];


                /*
                |--------------------------------------------------------------------------
                | Channex Rate Plans
                |--------------------------------------------------------------------------
                */
                $rateResponse =
                    $channex->ratePlans(
                        $property->channex_property_id
                    );

                $channexRatePlans =
                    $rateResponse['data'] ?? [];
            }

        } catch (Throwable $exception) {

            /*
            |--------------------------------------------------------------------------
            | API Error
            |--------------------------------------------------------------------------
            */
            $error = $exception->getMessage();
        }


        /*
        |--------------------------------------------------------------------------
        | Render View
        |--------------------------------------------------------------------------
        */
        return view(
            'pms.channex.index',
            compact(
                'property',
                'roomTypes',
                'ratePlans',
                'connected',
                'error',
                'channexProperties',
                'channexRoomTypes',
                'channexRatePlans'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Map PMS Property → Channex Property
    |--------------------------------------------------------------------------
    */
    public function mapProperty(
        Request $request
    ) {
        $property = Property::firstOrFail();


        /*
        |--------------------------------------------------------------------------
        | Validate Channex Property ID
        |--------------------------------------------------------------------------
        */
        $validated = $request->validate([
            'channex_property_id' => [
                'required',
                'string',
                'max:255',
            ],
        ]);


        /*
        |--------------------------------------------------------------------------
        | Save Mapping
        |--------------------------------------------------------------------------
        */
        $property->update([
            'channex_property_id' =>
                $validated['channex_property_id'],
        ]);


        return redirect()
            ->route('pms.channex.index')
            ->with(
                'success',
                'Channex Property mapped successfully.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Remove Channex Property Mapping
    |--------------------------------------------------------------------------
    |
    | Khi disconnect Property:
    |
    | - Xóa Rate Plan Mapping
    | - Xóa Room Type Mapping
    | - Xóa Property Mapping
    |
    | Không xóa dữ liệu bên Channex.
    |
    */
    public function disconnect()
    {
        $property = Property::firstOrFail();


        DB::transaction(function () use ($property) {

            /*
            |--------------------------------------------------------------------------
            | Remove Rate Plan mappings
            |--------------------------------------------------------------------------
            */
            RatePlan::where(
                'property_id',
                $property->id
            )->update([
                'channex_rate_plan_id' => null,
                'channex_sell_mode' => null,
            ]);


            /*
            |--------------------------------------------------------------------------
            | Remove Room Type mappings
            |--------------------------------------------------------------------------
            */
            RoomType::where(
                'property_id',
                $property->id
            )->update([
                'channex_room_type_id' => null,
            ]);


            /*
            |--------------------------------------------------------------------------
            | Remove Property mapping
            |--------------------------------------------------------------------------
            */
            $property->update([
                'channex_property_id' => null,
            ]);
        });


        return redirect()
            ->route('pms.channex.index')
            ->with(
                'success',
                'Channex mapping removed.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Map PMS Room Types → Channex Room Types
    |--------------------------------------------------------------------------
    */
    public function mapRoomTypes(
        Request $request,
        ChannexService $channex
    ) {
        $property = Property::firstOrFail();


        /*
        |--------------------------------------------------------------------------
        | Property must be mapped first
        |--------------------------------------------------------------------------
        */
        if (!$property->channex_property_id) {

            return redirect()
                ->route('pms.channex.index')
                ->with(
                    'error',
                    'Please map the Property first.'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Get Channex Room Types
        |--------------------------------------------------------------------------
        */
        $response = $channex->roomTypes(
            $property->channex_property_id
        );

        $remoteRoomTypes =
            $response['data'] ?? [];


        /*
        |--------------------------------------------------------------------------
        | Build valid Channex Room Type IDs
        |--------------------------------------------------------------------------
        */
        $validChannexIds = collect(
            $remoteRoomTypes
        )
            ->map(function ($item) {

                return $item['id']
                    ?? ($item['attributes']['id'] ?? null);

            })
            ->filter()
            ->values()
            ->all();


        /*
        |--------------------------------------------------------------------------
        | Validate form
        |--------------------------------------------------------------------------
        */
        $validated = $request->validate([
            'mappings' => [
                'required',
                'array',
            ],

            'mappings.*' => [
                'nullable',
                'string',
            ],
        ]);


        /*
        |--------------------------------------------------------------------------
        | Selected Channex IDs
        |--------------------------------------------------------------------------
        */
        $selectedIds = collect(
            $validated['mappings']
        )
            ->filter(
                fn ($value) =>
                    !empty($value)
            );


        /*
        |--------------------------------------------------------------------------
        | Prevent duplicate Room Type mapping
        |--------------------------------------------------------------------------
        |
        | Một Channex Room Type không được map
        | với nhiều PMS Room Type.
        |
        */
        if (
            $selectedIds->count()
            !==
            $selectedIds->unique()->count()
        ) {

            throw ValidationException::withMessages([
                'mappings' =>
                    'A Channex Room Type can only be mapped once.',
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Validate Channex Room Type IDs
        |--------------------------------------------------------------------------
        */
        foreach ($selectedIds as $channexId) {

            if (
                !in_array(
                    $channexId,
                    $validChannexIds,
                    true
                )
            ) {

                throw ValidationException::withMessages([
                    'mappings' =>
                        'Invalid Channex Room Type selected.',
                ]);
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Save Room Type mappings
        |--------------------------------------------------------------------------
        */
        DB::transaction(function () use (
            $property,
            $validated
        ) {

            $roomTypes = RoomType::where(
                'property_id',
                $property->id
            )->get();


            foreach ($roomTypes as $roomType) {

                $channexId =
                    $validated['mappings'][
                        $roomType->id
                    ] ?? null;


                /*
                 * Nếu Room Type mapping thay đổi,
                 * Rate Plan Mapping cũ có thể không còn hợp lệ.
                 *
                 * Vì demo hiện tại đơn giản,
                 * nếu đổi Room Mapping thì xóa Rate Mapping liên quan.
                 */
                if (
                    $roomType->channex_room_type_id
                    !==
                    ($channexId ?: null)
                ) {

                    RatePlan::where(
                        'room_type_id',
                        $roomType->id
                    )->update([
                        'channex_rate_plan_id' =>
                            null,

                        'channex_sell_mode' =>
                            null,
                    ]);
                }


                $roomType->update([
                    'channex_room_type_id' =>
                        $channexId ?: null,
                ]);
            }
        });


        return redirect()
            ->route('pms.channex.index')
            ->with(
                'success',
                'Room Type mapping saved successfully.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Map PMS Rate Plans → Channex Rate Plans
    |--------------------------------------------------------------------------
    |
    | Ví dụ:
    |
    | PMS
    | Deluxe / BAR
    |
    |          ↓
    |
    | Channex
    | Deluxe / Standard Rate
    |
    */
    public function mapRatePlans(
        Request $request,
        ChannexService $channex
    ) {
        $property = Property::firstOrFail();


        /*
        |--------------------------------------------------------------------------
        | Property must be mapped first
        |--------------------------------------------------------------------------
        */
        if (!$property->channex_property_id) {

            return redirect()
                ->route('pms.channex.index')
                ->with(
                    'error',
                    'Please map the Property first.'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Get Channex Rate Plans
        |--------------------------------------------------------------------------
        */
        $response = $channex->ratePlans(
            $property->channex_property_id
        );

        $remoteRatePlans =
            $response['data'] ?? [];


        /*
        |--------------------------------------------------------------------------
        | Index Channex Rate Plans by ID
        |--------------------------------------------------------------------------
        |
        | Ví dụ:
        |
        | abc-123 => Standard Rate
        | def-456 => Non Refundable
        |
        */
        $remoteById = collect(
            $remoteRatePlans
        )->keyBy(function ($item) {

            return $item['id']
                ?? ($item['attributes']['id'] ?? null);

        });


        /*
        |--------------------------------------------------------------------------
        | Validate Mapping Input
        |--------------------------------------------------------------------------
        |
        | Form gửi:
        |
        | mappings[1] = channex-rate-plan-id
        | mappings[2] = channex-rate-plan-id
        |
        */
        $validated = $request->validate([
            'mappings' => [
                'required',
                'array',
            ],

            'mappings.*' => [
                'nullable',
                'string',
            ],
        ]);


        /*
        |--------------------------------------------------------------------------
        | Remove empty values
        |--------------------------------------------------------------------------
        */
        $selectedIds = collect(
            $validated['mappings']
        )->filter();


        /*
        |--------------------------------------------------------------------------
        | Prevent duplicate mapping
        |--------------------------------------------------------------------------
        |
        | Một Channex Rate Plan không được map
        | với nhiều PMS Rate Plan.
        |
        */
        if (
            $selectedIds->count()
            !==
            $selectedIds->unique()->count()
        ) {

            throw ValidationException::withMessages([
                'mappings' =>
                    'A Channex Rate Plan can only be mapped once.',
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | PMS Rate Plans
        |--------------------------------------------------------------------------
        */
        $localRatePlans = RatePlan::with(
            'roomType'
        )
            ->where(
                'property_id',
                $property->id
            )
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Validate everything first
        |--------------------------------------------------------------------------
        */
        foreach ($localRatePlans as $ratePlan) {

            $remoteId =
                $validated['mappings'][
                    $ratePlan->id
                ] ?? null;


            /*
             * Not mapped is allowed.
             */
            if (!$remoteId) {
                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | Make sure Channex Rate Plan exists
            |--------------------------------------------------------------------------
            */
            $remote =
                $remoteById->get(
                    $remoteId
                );


            if (!$remote) {

                throw ValidationException::withMessages([
                    'mappings' =>
                        "Invalid Channex Rate Plan selected for {$ratePlan->name}.",
                ]);
            }


            $attributes =
                $remote['attributes'] ?? [];


            /*
            |--------------------------------------------------------------------------
            | Rate Plan must belong to mapped Room Type
            |--------------------------------------------------------------------------
            |
            | Ví dụ:
            |
            | PMS Deluxe / BAR
            |
            | chỉ được map:
            |
            | Channex Deluxe / Standard Rate
            |
            | không được map:
            |
            | Channex Suite / Standard Rate
            |
            */
            if (
                !$ratePlan->roomType
                ||
                !$ratePlan->roomType
                    ->channex_room_type_id
            ) {

                throw ValidationException::withMessages([
                    'mappings' =>
                        "Room Type của Rate Plan {$ratePlan->name} chưa được map với Channex.",
                ]);
            }


            $remoteRoomTypeId =
                $attributes['room_type_id']
                ?? null;


            if (
                $remoteRoomTypeId
                !==
                $ratePlan->roomType
                    ->channex_room_type_id
            ) {

                throw ValidationException::withMessages([
                    'mappings' =>
                        "Rate Plan {$ratePlan->name} không thuộc đúng Room Type đã map.",
                ]);
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Save Rate Plan mappings
        |--------------------------------------------------------------------------
        */
        DB::transaction(function () use (
            $localRatePlans,
            $validated,
            $remoteById
        ) {

            foreach ($localRatePlans as $ratePlan) {

                $remoteId =
                    $validated['mappings'][
                        $ratePlan->id
                    ] ?? null;


                /*
                |--------------------------------------------------------------------------
                | User selected Not Mapped
                |--------------------------------------------------------------------------
                */
                if (!$remoteId) {

                    $ratePlan->update([
                        'channex_rate_plan_id' =>
                            null,

                        'channex_sell_mode' =>
                            null,
                    ]);

                    continue;
                }


                /*
                |--------------------------------------------------------------------------
                | Channex Rate Plan
                |--------------------------------------------------------------------------
                */
                $remote =
                    $remoteById->get(
                        $remoteId
                    );


                $attributes =
                    $remote['attributes'] ?? [];


                /*
                |--------------------------------------------------------------------------
                | Save Mapping
                |--------------------------------------------------------------------------
                */
                $ratePlan->update([
                    'channex_rate_plan_id' =>
                        $remoteId,

                    'channex_sell_mode' =>
                        $attributes['sell_mode']
                        ?? null,
                ]);
            }
        });


        return redirect()
            ->route('pms.channex.index')
            ->with(
                'success',
                'Rate Plan mapping saved successfully.'
            );
    }
}