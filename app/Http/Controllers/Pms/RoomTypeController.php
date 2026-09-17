<?php

namespace App\Http\Controllers\Pms;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\RoomType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoomTypeController extends Controller
{
    public function index(Request $request)
    {
        $property = Property::firstOrFail();

        $query = RoomType::where('property_id', $property->id);

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $roomTypes = $query
            ->orderBy('name')
            ->get();

        return view('pms.room-types.index', compact(
            'property',
            'roomTypes'
        ));
    }

    public function create()
    {
        $property = Property::firstOrFail();

        return view('pms.room-types.create', compact(
            'property'
        ));
    }

    public function store(Request $request)
    {
        $property = Property::firstOrFail();

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'code' => [
                'required',
                'string',
                'max:50',

                Rule::unique('room_types')
                    ->where(
                        fn ($query) =>
                        $query->where(
                            'property_id',
                            $property->id
                        )
                    ),
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'total_rooms' => [
                'required',
                'integer',
                'min:1',
            ],

            'max_adults' => [
                'required',
                'integer',
                'min:1',
            ],

            'max_children' => [
                'required',
                'integer',
                'min:0',
            ],

            'base_price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'status' => [
                'required',
                Rule::in([
                    'active',
                    'inactive',
                ]),
            ],
        ]);

        $validated['property_id'] = $property->id;

        RoomType::create($validated);

        return redirect()
            ->route('pms.room-types.index')
            ->with(
                'success',
                'Room Type created successfully.'
            );
    }

    public function edit(RoomType $roomType)
    {
        $property = Property::firstOrFail();

        abort_if(
            $roomType->property_id !== $property->id,
            404
        );

        return view(
            'pms.room-types.edit',
            compact(
                'property',
                'roomType'
            )
        );
    }

    public function update(
        Request $request,
        RoomType $roomType
    ) {
        $property = Property::firstOrFail();

        abort_if(
            $roomType->property_id !== $property->id,
            404
        );

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'code' => [
                'required',
                'string',
                'max:50',

                Rule::unique('room_types')
                    ->where(
                        fn ($query) =>
                        $query->where(
                            'property_id',
                            $property->id
                        )
                    )
                    ->ignore($roomType->id),
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'total_rooms' => [
                'required',
                'integer',
                'min:1',
            ],

            'max_adults' => [
                'required',
                'integer',
                'min:1',
            ],

            'max_children' => [
                'required',
                'integer',
                'min:0',
            ],

            'base_price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'status' => [
                'required',
                Rule::in([
                    'active',
                    'inactive',
                ]),
            ],
        ]);

        $roomType->update($validated);

        return redirect()
            ->route('pms.room-types.index')
            ->with(
                'success',
                'Room Type updated successfully.'
            );
    }

    public function destroy(RoomType $roomType)
    {
        $property = Property::firstOrFail();

        abort_if(
            $roomType->property_id !== $property->id,
            404
        );

        $roomType->delete();

        return redirect()
            ->route('pms.room-types.index')
            ->with(
                'success',
                'Room Type deleted successfully.'
            );
    }
}