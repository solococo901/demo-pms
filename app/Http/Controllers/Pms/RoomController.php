<?php

namespace App\Http\Controllers\Pms;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoomController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Rooms List
    |--------------------------------------------------------------------------
    */
    public function index(Request $request)
    {
        $property = Property::firstOrFail();

        $query = Room::with('roomType')
            ->where(
                'property_id',
                $property->id
            );


        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        |
        | Search theo:
        | - Room Number
        | - Floor
        |
        */
        if ($request->filled('search')) {

            $search = trim(
                $request->search
            );

            $query->where(function ($q) use ($search) {

                $q->where(
                    'room_number',
                    'like',
                    '%' . $search . '%'
                )
                ->orWhere(
                    'floor',
                    'like',
                    '%' . $search . '%'
                );
            });
        }


        /*
        |--------------------------------------------------------------------------
        | Filter Room Type
        |--------------------------------------------------------------------------
        */
        if ($request->filled('room_type')) {

            $query->where(
                'room_type_id',
                $request->room_type
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Filter Room Status
        |--------------------------------------------------------------------------
        */
        if ($request->filled('status')) {

            $query->where(
                'status',
                $request->status
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Filter Housekeeping Status
        |--------------------------------------------------------------------------
        */
        if ($request->filled('housekeeping_status')) {

            $query->where(
                'housekeeping_status',
                $request->housekeeping_status
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
        | Room Types for filter
        |--------------------------------------------------------------------------
        */
        $roomTypes = RoomType::where(
            'property_id',
            $property->id
        )
            ->where(
                'status',
                'active'
            )
            ->orderBy('name')
            ->get();


        return view(
            'pms.rooms.index',
            compact(
                'property',
                'rooms',
                'roomTypes'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Create Room
    |--------------------------------------------------------------------------
    */
    public function create()
    {
        $property = Property::firstOrFail();


        $roomTypes = RoomType::where(
            'property_id',
            $property->id
        )
            ->where(
                'status',
                'active'
            )
            ->orderBy('name')
            ->get();


        return view(
            'pms.rooms.create',
            compact(
                'property',
                'roomTypes'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Store Room
    |--------------------------------------------------------------------------
    */
    public function store(Request $request)
    {
        $property = Property::firstOrFail();


        $validated = $this->validateRoom(
            $request,
            $property
        );


        $validated['property_id'] =
            $property->id;


        Room::create(
            $validated
        );


        return redirect()
            ->route('pms.rooms.index')
            ->with(
                'success',
                'Room created successfully.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Edit Room
    |--------------------------------------------------------------------------
    */
    public function edit(Room $room)
    {
        $property = Property::firstOrFail();


        /*
         * Không cho truy cập Room
         * thuộc Property khác.
         */
        abort_if(
            $room->property_id
                !==
            $property->id,
            404
        );


        $roomTypes = RoomType::where(
            'property_id',
            $property->id
        )
            ->where(
                'status',
                'active'
            )
            ->orderBy('name')
            ->get();


        return view(
            'pms.rooms.edit',
            compact(
                'property',
                'room',
                'roomTypes'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Update Room
    |--------------------------------------------------------------------------
    */
    public function update(
        Request $request,
        Room $room
    ) {
        $property = Property::firstOrFail();


        abort_if(
            $room->property_id
                !==
            $property->id,
            404
        );


        $validated = $this->validateRoom(
            $request,
            $property,
            $room
        );


        $room->update(
            $validated
        );


        return redirect()
            ->route('pms.rooms.index')
            ->with(
                'success',
                'Room updated successfully.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Delete Room
    |--------------------------------------------------------------------------
    */
    public function destroy(Room $room)
    {
        $property = Property::firstOrFail();


        abort_if(
            $room->property_id
                !==
            $property->id,
            404
        );


        /*
         * Phase 1:
         *
         * Cho phép xóa Room.
         *
         * Sau này khi có Reservation:
         * không nên xóa Room đã từng có booking,
         * mà chuyển status thành inactive/out_of_order.
         */
        $room->delete();


        return redirect()
            ->route('pms.rooms.index')
            ->with(
                'success',
                'Room deleted successfully.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Validate Room
    |--------------------------------------------------------------------------
    */
    private function validateRoom(
        Request $request,
        Property $property,
        ?Room $room = null
    ): array {

        /*
        |--------------------------------------------------------------------------
        | Valid Room Types
        |--------------------------------------------------------------------------
        |
        | Chỉ được chọn Room Type
        | thuộc Property hiện tại.
        |
        */
        $roomTypeIds = RoomType::where(
            'property_id',
            $property->id
        )
            ->pluck('id')
            ->all();


        return $request->validate([

            /*
            |--------------------------------------------------------------------------
            | Room Type
            |--------------------------------------------------------------------------
            */
            'room_type_id' => [
                'required',
                'integer',
                Rule::in(
                    $roomTypeIds
                ),
            ],


            /*
            |--------------------------------------------------------------------------
            | Room Number
            |--------------------------------------------------------------------------
            |
            | Không được trùng trong cùng Property.
            |
            */
            'room_number' => [
                'required',
                'string',
                'max:50',

                Rule::unique(
                    'rooms',
                    'room_number'
                )
                    ->where(
                        fn ($query) =>
                            $query->where(
                                'property_id',
                                $property->id
                            )
                    )
                    ->ignore(
                        $room?->id
                    ),
            ],


            /*
            |--------------------------------------------------------------------------
            | Floor
            |--------------------------------------------------------------------------
            */
            'floor' => [
                'nullable',
                'string',
                'max:50',
            ],


            /*
            |--------------------------------------------------------------------------
            | Room Status
            |--------------------------------------------------------------------------
            */
            'status' => [
                'required',

                Rule::in([
                    'available',
                    'occupied',
                    'out_of_order',
                    'maintenance',
                ]),
            ],


            /*
            |--------------------------------------------------------------------------
            | Housekeeping
            |--------------------------------------------------------------------------
            */
            'housekeeping_status' => [
                'required',

                Rule::in([
                    'clean',
                    'dirty',
                    'cleaning',
                    'inspected',
                ]),
            ],


            /*
            |--------------------------------------------------------------------------
            | Notes
            |--------------------------------------------------------------------------
            */
            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);
    }
}