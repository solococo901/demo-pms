<?php

namespace App\Http\Controllers\Pms;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\RatePlan;
use App\Models\RoomType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;


class RatePlanController extends Controller
{
    public function index()
    {
        $property = Property::firstOrFail();

        $ratePlans = RatePlan::with('roomType')
            ->where('property_id', $property->id)
            ->orderBy('room_type_id')
            ->orderBy('name')
            ->get();

        return view(
            'pms.rate-plans.index',
            compact(
                'property',
                'ratePlans'
            )
        );
    }


    public function create()
    {
        $property = Property::firstOrFail();

        $roomTypes = RoomType::where(
            'property_id',
            $property->id
        )
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view(
            'pms.rate-plans.create',
            compact(
                'property',
                'roomTypes'
            )
        );
    }


    public function store(Request $request)
    {
        $property = Property::firstOrFail();

        $validated = $this->validateRatePlan(
            $request,
            $property
        );

        $validated['property_id'] =
            $property->id;

        RatePlan::create($validated);

        return redirect()
            ->route('pms.rate-plans.index')
            ->with(
                'success',
                'Rate Plan created successfully.'
            );
    }


    public function edit(RatePlan $ratePlan)
    {
        $property = Property::firstOrFail();

        abort_if(
            $ratePlan->property_id !== $property->id,
            404
        );

        $roomTypes = RoomType::where(
            'property_id',
            $property->id
        )
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view(
            'pms.rate-plans.edit',
            compact(
                'property',
                'ratePlan',
                'roomTypes'
            )
        );
    }


    public function update(
        Request $request,
        RatePlan $ratePlan
    ) {
        $property = Property::firstOrFail();

        abort_if(
            $ratePlan->property_id !== $property->id,
            404
        );

        $validated = $this->validateRatePlan(
            $request,
            $property,
            $ratePlan
        );

        $ratePlan->update($validated);

        return redirect()
            ->route('pms.rate-plans.index')
            ->with(
                'success',
                'Rate Plan updated successfully.'
            );
    }


    public function destroy(RatePlan $ratePlan)
    {
        $property = Property::firstOrFail();

        abort_if(
            $ratePlan->property_id !== $property->id,
            404
        );

        $ratePlan->delete();

        return redirect()
            ->route('pms.rate-plans.index')
            ->with(
                'success',
                'Rate Plan deleted successfully.'
            );
    }


    private function validateRatePlan(
        Request $request,
        Property $property,
        ?RatePlan $ratePlan = null
    ): array {

        $roomTypeIds = RoomType::where(
            'property_id',
            $property->id
        )->pluck('id')->all();


        return $request->validate([
            'room_type_id' => [
                'required',
                'integer',
                Rule::in($roomTypeIds),
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'code' => [
                'required',
                'string',
                'max:50',
            ],

            'base_rate' => [
                'required',
                'numeric',
                'min:0',
            ],

            'min_stay' => [
                'required',
                'integer',
                'min:1',
            ],

            'stop_sell' => [
                'nullable',
                'boolean',
            ],

            'status' => [
                'required',
                Rule::in([
                    'active',
                    'inactive',
                ]),
            ],
        ]);
    }
}