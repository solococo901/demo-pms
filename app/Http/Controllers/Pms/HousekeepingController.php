<?php

namespace App\Http\Controllers\Pms;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HousekeepingController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Housekeeping Board
    |--------------------------------------------------------------------------
    */
    public function index(Request $request)
    {
        $property = Property::firstOrFail();


        /*
        |--------------------------------------------------------------------------
        | Query
        |--------------------------------------------------------------------------
        */
        $query = Room::with('roomType')
            ->where(
                'property_id',
                $property->id
            );


        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */
        if ($request->filled('search')) {

            $search = trim(
                $request->search
            );

            $query->where(
                function ($q) use ($search) {

                    $q->where(
                        'room_number',
                        'like',
                        '%' . $search . '%'
                    )
                        ->orWhere(
                            'floor',
                            'like',
                            '%' . $search . '%'
                        )
                        ->orWhereHas(
                            'roomType',
                            function ($roomTypeQuery) use ($search) {

                                $roomTypeQuery->where(
                                    'name',
                                    'like',
                                    '%' . $search . '%'
                                );
                            }
                        );
                }
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Housekeeping Status Filter
        |--------------------------------------------------------------------------
        */
        if (
            $request->filled(
                'housekeeping_status'
            )
        ) {

            $query->where(
                'housekeeping_status',
                $request->housekeeping_status
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Operational Status Filter
        |--------------------------------------------------------------------------
        */
        if (
            $request->filled(
                'status'
            )
        ) {

            $query->where(
                'status',
                $request->status
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Floor Filter
        |--------------------------------------------------------------------------
        */
        if (
            $request->filled(
                'floor'
            )
        ) {

            $query->where(
                'floor',
                $request->floor
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Rooms
        |--------------------------------------------------------------------------
        */
        $rooms = $query
            ->orderBy('floor')
            ->orderBy('room_number')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | All Rooms - Stats
        |--------------------------------------------------------------------------
        |
        | Stats không bị ảnh hưởng bởi filter.
        |
        */
        $allRooms = Room::where(
            'property_id',
            $property->id
        )->get();


        $stats = [
            'total' =>
                $allRooms->count(),

            'clean' =>
                $allRooms
                    ->where(
                        'housekeeping_status',
                        'clean'
                    )
                    ->count(),

            'dirty' =>
                $allRooms
                    ->where(
                        'housekeeping_status',
                        'dirty'
                    )
                    ->count(),

            'cleaning' =>
                $allRooms
                    ->where(
                        'housekeeping_status',
                        'cleaning'
                    )
                    ->count(),

            'inspected' =>
                $allRooms
                    ->where(
                        'housekeeping_status',
                        'inspected'
                    )
                    ->count(),
        ];


        /*
        |--------------------------------------------------------------------------
        | Floors
        |--------------------------------------------------------------------------
        */
        $floors = Room::where(
            'property_id',
            $property->id
        )
            ->whereNotNull('floor')
            ->where(
                'floor',
                '!=',
                ''
            )
            ->distinct()
            ->orderBy('floor')
            ->pluck('floor');


        /*
        |--------------------------------------------------------------------------
        | View
        |--------------------------------------------------------------------------
        */
        return view(
            'pms.housekeeping.index',
            compact(
                'property',
                'rooms',
                'stats',
                'floors'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Update Housekeeping Status
    |--------------------------------------------------------------------------
    */
    public function updateStatus(
        Request $request,
        Room $room
    ) {
        $property = Property::firstOrFail();


        /*
        |--------------------------------------------------------------------------
        | Security
        |--------------------------------------------------------------------------
        */
        abort_if(
            (int) $room->property_id
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
                'housekeeping_status' => [
                    'required',

                    Rule::in([
                        'clean',
                        'dirty',
                        'cleaning',
                        'inspected',
                    ]),
                ],
            ]);


        /*
        |--------------------------------------------------------------------------
        | Update
        |--------------------------------------------------------------------------
        */
        $room->update([
            'housekeeping_status' =>
                $validated[
                    'housekeeping_status'
                ],
        ]);


        /*
        |--------------------------------------------------------------------------
        | Friendly Label
        |--------------------------------------------------------------------------
        */
        $label = match (
            $validated[
                'housekeeping_status'
            ]
        ) {
            'clean' =>
                'Clean',

            'dirty' =>
                'Dirty',

            'cleaning' =>
                'Cleaning',

            'inspected' =>
                'Inspected',

            default =>
                ucfirst(
                    $validated[
                        'housekeeping_status'
                    ]
                ),
        };


        return back()->with(
            'success',
            "Room {$room->room_number} housekeeping status changed to {$label}."
        );
    }
}