<?php

namespace App\Http\Controllers\Pms;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\RatePlan;
use App\Models\RateCalendar;
use App\Services\ChannexService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class RateCalendarController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Rate Calendar
    |--------------------------------------------------------------------------
    */
    public function index(Request $request)
    {
        $property = Property::firstOrFail();


        /*
        |--------------------------------------------------------------------------
        | 7-day range
        |--------------------------------------------------------------------------
        */
        $startDate = $request->filled('start')
            ? CarbonImmutable::parse(
                $request->start
            )->startOfDay()
            : CarbonImmutable::today();


        $dates = collect(
            range(0, 6)
        )->map(
            fn ($day) =>
                $startDate->addDays($day)
        );


        /*
        |--------------------------------------------------------------------------
        | Active PMS Rate Plans
        |--------------------------------------------------------------------------
        */
        $ratePlans = RatePlan::with('roomType')
            ->where(
                'property_id',
                $property->id
            )
            ->where(
                'status',
                'active'
            )
            ->orderBy('room_type_id')
            ->orderBy('name')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Create missing calendar records
        |--------------------------------------------------------------------------
        |
        | Nếu ngày chưa có Rate Calendar,
        | tạo dữ liệu mặc định từ Rate Plan.
        |
        */
        foreach ($ratePlans as $ratePlan) {

            foreach ($dates as $date) {

                $dateString =
                    $date->format('Y-m-d');


                $calendar = RateCalendar::where(
                    'rate_plan_id',
                    $ratePlan->id
                )
                    ->whereDate(
                        'date',
                        $dateString
                    )
                    ->first();


                if (!$calendar) {

                    RateCalendar::create([
                        'property_id' =>
                            $property->id,

                        'rate_plan_id' =>
                            $ratePlan->id,

                        'date' =>
                            $dateString,

                        'rate' =>
                            $ratePlan->base_rate,

                        'min_stay' =>
                            $ratePlan->min_stay,

                        'stop_sell' =>
                            $ratePlan->stop_sell,

                        'sync_status' =>
                            'pending',

                        'synced_at' =>
                            null,
                    ]);
                }
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Load calendar records
        |--------------------------------------------------------------------------
        */
        $calendars = RateCalendar::where(
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


                return $item->rate_plan_id
                    . '_'
                    . $date;
            });


        return view(
            'pms.rate-calendar.index',
            compact(
                'property',
                'ratePlans',
                'dates',
                'calendars',
                'startDate'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Save + Sync Rate Calendar
    |--------------------------------------------------------------------------
    */
    public function update(
        Request $request,
        ChannexService $channex
    ) {
        $property = Property::firstOrFail();


        /*
        |--------------------------------------------------------------------------
        | Validate Input
        |--------------------------------------------------------------------------
        */
        $request->validate([
            'start_date' => [
                'required',
                'date',
            ],

            'rates' => [
                'required',
                'array',
            ],

            'rates.*' => [
                'required',
                'array',
            ],

            'rates.*.*.rate' => [
                'required',
                'numeric',
                'gt:0',
            ],

            'rates.*.*.min_stay' => [
                'required',
                'integer',
                'min:1',
            ],

            'rates.*.*.stop_sell' => [
                'required',
                'boolean',
            ],
        ]);


        /*
        |--------------------------------------------------------------------------
        | Get PMS Rate Plans
        |--------------------------------------------------------------------------
        |
        | Chỉ lấy Rate Plan thuộc Property hiện tại.
        |
        */
        $ratePlans = RatePlan::with(
            'roomType'
        )
            ->where(
                'property_id',
                $property->id
            )
            ->get()
            ->keyBy('id');


        /*
        |--------------------------------------------------------------------------
        | Channex Payload
        |--------------------------------------------------------------------------
        */
        $channexValues = [];


        /*
        |--------------------------------------------------------------------------
        | Changed Calendar IDs
        |--------------------------------------------------------------------------
        |
        | Những ô Rate Calendar thực sự thay đổi trong PMS.
        |
        */
        $changedCalendarIds = [];


        /*
        |--------------------------------------------------------------------------
        | Sync Calendar IDs
        |--------------------------------------------------------------------------
        |
        | Những ô thực sự đủ điều kiện
        | và được gửi sang Channex.
        |
        */
        $syncCalendarIds = [];


        /*
        |--------------------------------------------------------------------------
        | Save Local PMS Data First
        |--------------------------------------------------------------------------
        */
        DB::transaction(function () use (
            $request,
            $property,
            $ratePlans,
            &$channexValues,
            &$changedCalendarIds,
            &$syncCalendarIds
        ) {

            foreach (
                $request->rates
                as
                $ratePlanId => $dateValues
            ) {

                /*
                |--------------------------------------------------------------------------
                | Find Rate Plan
                |--------------------------------------------------------------------------
                */
                $ratePlan =
                    $ratePlans->get(
                        (int) $ratePlanId
                    );


                /*
                 * Không nhận Rate Plan
                 * không thuộc Property hiện tại.
                 */
                if (!$ratePlan) {
                    continue;
                }


                foreach (
                    $dateValues
                    as
                    $date => $values
                ) {

                    /*
                    |--------------------------------------------------------------------------
                    | Normalize Date
                    |--------------------------------------------------------------------------
                    */
                    $date =
                        CarbonImmutable::parse(
                            $date
                        )->format('Y-m-d');


                    /*
                    |--------------------------------------------------------------------------
                    | Normalize Rate
                    |--------------------------------------------------------------------------
                    */
                    $rate =
                        (float) $values['rate'];


                    /*
                    |--------------------------------------------------------------------------
                    | Normalize Minimum Stay
                    |--------------------------------------------------------------------------
                    */
                    $minStay =
                        (int) $values['min_stay'];


                    /*
                    |--------------------------------------------------------------------------
                    | Normalize Stop Sell
                    |--------------------------------------------------------------------------
                    */
                    $stopSell =
                        filter_var(
                            $values['stop_sell'],
                            FILTER_VALIDATE_BOOLEAN
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | Find Existing Calendar
                    |--------------------------------------------------------------------------
                    */
                    $calendar =
                        RateCalendar::where(
                            'rate_plan_id',
                            $ratePlan->id
                        )
                            ->whereDate(
                                'date',
                                $date
                            )
                            ->first();


                    /*
                    |--------------------------------------------------------------------------
                    | Detect Changes
                    |--------------------------------------------------------------------------
                    */
                    $changed =
                        !$calendar
                        ||
                        (float) $calendar->rate
                            !==
                            $rate
                        ||
                        (int) $calendar->min_stay
                            !==
                            $minStay
                        ||
                        (bool) $calendar->stop_sell
                            !==
                            $stopSell;


                    /*
                    |--------------------------------------------------------------------------
                    | Update Existing Calendar
                    |--------------------------------------------------------------------------
                    */
                    if ($calendar) {

                        $calendar->update([
                            'property_id' =>
                                $property->id,

                            'rate' =>
                                $rate,

                            'min_stay' =>
                                $minStay,

                            'stop_sell' =>
                                $stopSell,

                            /*
                             * Nếu có thay đổi:
                             * chuyển về pending.
                             *
                             * Nếu không thay đổi:
                             * giữ status cũ.
                             */
                            'sync_status' =>
                                $changed
                                    ? 'pending'
                                    : $calendar->sync_status,

                            /*
                             * Nếu thay đổi:
                             * dữ liệu cũ không còn được xem
                             * là đã sync.
                             */
                            'synced_at' =>
                                $changed
                                    ? null
                                    : $calendar->synced_at,
                        ]);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Create Calendar
                    |--------------------------------------------------------------------------
                    */
                    else {

                        $calendar =
                            RateCalendar::create([
                                'property_id' =>
                                    $property->id,

                                'rate_plan_id' =>
                                    $ratePlan->id,

                                'date' =>
                                    $date,

                                'rate' =>
                                    $rate,

                                'min_stay' =>
                                    $minStay,

                                'stop_sell' =>
                                    $stopSell,

                                'sync_status' =>
                                    'pending',

                                'synced_at' =>
                                    null,
                            ]);
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Nothing Changed
                    |--------------------------------------------------------------------------
                    |
                    | Không thay đổi:
                    |
                    | - Không thêm vào changed list
                    | - Không gửi Channex
                    | - Không đổi status
                    |
                    */
                    if (!$changed) {
                        continue;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Track Changed Calendar
                    |--------------------------------------------------------------------------
                    */
                    $changedCalendarIds[] =
                        $calendar->id;


                    /*
                    |--------------------------------------------------------------------------
                    | Channex Property Mapping
                    |--------------------------------------------------------------------------
                    |
                    | Property chưa map:
                    |
                    | vẫn lưu PMS
                    | nhưng không gửi Channex.
                    |
                    */
                    if (
                        !$property
                            ->channex_property_id
                    ) {
                        continue;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Channex Rate Plan Mapping
                    |--------------------------------------------------------------------------
                    */
                    if (
                        !$ratePlan
                            ->channex_rate_plan_id
                    ) {
                        continue;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Phase 1 - per_room only
                    |--------------------------------------------------------------------------
                    |
                    | per_person cần xử lý pricing
                    | theo occupancy nên chưa hỗ trợ.
                    |
                    */
                    if (
                        $ratePlan
                            ->channex_sell_mode
                        !==
                        'per_room'
                    ) {
                        continue;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Build Channex Payload
                    |--------------------------------------------------------------------------
                    */
                    $channexValues[] = [
                        'property_id' =>
                            $property
                                ->channex_property_id,

                        'rate_plan_id' =>
                            $ratePlan
                                ->channex_rate_plan_id,

                        'date' =>
                            $date,

                        'rate' =>
                            number_format(
                                $rate,
                                2,
                                '.',
                                ''
                            ),

                        'min_stay_arrival' =>
                            $minStay,

                        'stop_sell' =>
                            $stopSell,
                    ];


                    /*
                    |--------------------------------------------------------------------------
                    | Track Actual Channex Sync Calendar
                    |--------------------------------------------------------------------------
                    |
                    | Chỉ những calendar ID này
                    | mới được đánh dấu Synced/Failed/Warning.
                    |
                    */
                    $syncCalendarIds[] =
                        $calendar->id;
                }
            }
        });


        /*
        |--------------------------------------------------------------------------
        | Remove Duplicate Changed IDs
        |--------------------------------------------------------------------------
        */
        $changedCalendarIds =
            array_values(
                array_unique(
                    $changedCalendarIds
                )
            );


        /*
        |--------------------------------------------------------------------------
        | Remove Duplicate Sync IDs
        |--------------------------------------------------------------------------
        */
        $syncCalendarIds =
            array_values(
                array_unique(
                    $syncCalendarIds
                )
            );


        /*
        |--------------------------------------------------------------------------
        | Nothing Changed
        |--------------------------------------------------------------------------
        |
        | User bấm Save & Sync nhưng không sửa gì.
        |
        */
        if (
            empty(
                $changedCalendarIds
            )
        ) {

            return redirect()
                ->route(
                    'pms.rate-calendar.index',
                    [
                        'start' =>
                            $request->start_date,
                    ]
                )
                ->with(
                    'success',
                    'Không có thay đổi nào cần lưu hoặc đồng bộ.'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Property Not Mapped
        |--------------------------------------------------------------------------
        |
        | PMS vẫn lưu bình thường.
        |
        | Các calendar thay đổi giữ:
        |
        | sync_status = pending
        |
        */
        if (
            !$property
                ->channex_property_id
        ) {

            return redirect()
                ->route(
                    'pms.rate-calendar.index',
                    [
                        'start' =>
                            $request->start_date,
                    ]
                )
                ->with(
                    'success',
                    'Rate Calendar đã lưu trong PMS. Property chưa được map với Channex.'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Nothing Eligible For Channex Sync
        |--------------------------------------------------------------------------
        |
        | Có thay đổi local nhưng có thể:
        |
        | - Rate Plan chưa map
        | - sell_mode không phải per_room
        |
        */
        if (
            empty($channexValues)
            ||
            empty($syncCalendarIds)
        ) {

            return redirect()
                ->route(
                    'pms.rate-calendar.index',
                    [
                        'start' =>
                            $request->start_date,
                    ]
                )
                ->with(
                    'success',
                    'Rate Calendar đã lưu trong PMS. Không có Rate Plan đã map với Channex ở chế độ per_room để đồng bộ.'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Sync To Channex
        |--------------------------------------------------------------------------
        */
        try {

            $response =
                $channex
                    ->updateRestrictions(
                        $channexValues
                    );


            /*
            |--------------------------------------------------------------------------
            | Channex Warnings
            |--------------------------------------------------------------------------
            |
            | Channex có thể trả request thành công
            | nhưng vẫn có warnings.
            |
            */
            $warnings =
                $response['meta']['warnings']
                ?? [];


            /*
            |--------------------------------------------------------------------------
            | Warning
            |--------------------------------------------------------------------------
            */
            if (
                !empty($warnings)
            ) {

                RateCalendar::whereIn(
                    'id',
                    $syncCalendarIds
                )->update([
                    'sync_status' =>
                        'warning',

                    'synced_at' =>
                        null,
                ]);


                return redirect()
                    ->route(
                        'pms.rate-calendar.index',
                        [
                            'start' =>
                                $request->start_date,
                        ]
                    )
                    ->with(
                        'error',
                        'Rate Calendar đã lưu nhưng Channex trả về warning.'
                    );
            }


            /*
            |--------------------------------------------------------------------------
            | Sync Success
            |--------------------------------------------------------------------------
            |
            | CHỈ đánh dấu những calendar
            | thực sự được gửi sang Channex.
            |
            */
            RateCalendar::whereIn(
                'id',
                $syncCalendarIds
            )->update([
                'sync_status' =>
                    'synced',

                'synced_at' =>
                    now(),
            ]);


            /*
            |--------------------------------------------------------------------------
            | Success Response
            |--------------------------------------------------------------------------
            */
            return redirect()
                ->route(
                    'pms.rate-calendar.index',
                    [
                        'start' =>
                            $request->start_date,
                    ]
                )
                ->with(
                    'success',
                    'Rate Calendar đã lưu và đồng bộ Channex thành công.'
                );


        } catch (Throwable $exception) {

            /*
            |--------------------------------------------------------------------------
            | Channex Sync Failed
            |--------------------------------------------------------------------------
            |
            | CHỈ đánh dấu failed những calendar
            | thực sự được gửi sang Channex.
            |
            */
            RateCalendar::whereIn(
                'id',
                $syncCalendarIds
            )->update([
                'sync_status' =>
                    'failed',

                'synced_at' =>
                    null,
            ]);


            /*
            |--------------------------------------------------------------------------
            | Failed Response
            |--------------------------------------------------------------------------
            */
            return redirect()
                ->route(
                    'pms.rate-calendar.index',
                    [
                        'start' =>
                            $request->start_date,
                    ]
                )
                ->with(
                    'error',
                    'Rate Calendar đã lưu trong PMS nhưng Channex sync thất bại: '
                    . $exception->getMessage()
                );
        }
    }
}