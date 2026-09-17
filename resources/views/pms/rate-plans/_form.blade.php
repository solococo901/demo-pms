<div class="grid md:grid-cols-2 gap-5">

    <div>
        <label class="block text-[11px] font-medium text-[#59636e] mb-2">
            Room Type
        </label>

        <select
            name="room_type_id"
            class="w-full h-[38px] px-3 bg-white border border-[#dce1e6] rounded-[3px] text-[12px] focus:border-[#1677ff]"
        >
            <option value="">Select Room Type</option>

            @foreach($roomTypes as $roomType)
                <option
                    value="{{ $roomType->id }}"
                    @selected(old('room_type_id', $ratePlan->room_type_id ?? '') == $roomType->id)
                >
                    {{ $roomType->name }}
                </option>
            @endforeach
        </select>
    </div>


    <div>
        <label class="block text-[11px] font-medium text-[#59636e] mb-2">
            Rate Plan Name
        </label>

        <input
            type="text"
            name="name"
            value="{{ old('name', $ratePlan->name ?? '') }}"
            placeholder="Best Available Rate"
            class="w-full h-[38px] px-3 border border-[#dce1e6] rounded-[3px] text-[12px] focus:border-[#1677ff]"
        >
    </div>


    <div>
        <label class="block text-[11px] font-medium text-[#59636e] mb-2">
            Code
        </label>

        <input
            type="text"
            name="code"
            value="{{ old('code', $ratePlan->code ?? '') }}"
            placeholder="BAR"
            class="w-full h-[38px] px-3 border border-[#dce1e6] rounded-[3px] text-[12px]"
        >
    </div>


    <div>
        <label class="block text-[11px] font-medium text-[#59636e] mb-2">
            Base Rate
        </label>

        <input
            type="number"
            min="0"
            name="base_rate"
            value="{{ old('base_rate', $ratePlan->base_rate ?? 0) }}"
            class="w-full h-[38px] px-3 border border-[#dce1e6] rounded-[3px] text-[12px]"
        >
    </div>


    <div>
        <label class="block text-[11px] font-medium text-[#59636e] mb-2">
            Minimum Stay
        </label>

        <input
            type="number"
            min="1"
            name="min_stay"
            value="{{ old('min_stay', $ratePlan->min_stay ?? 1) }}"
            class="w-full h-[38px] px-3 border border-[#dce1e6] rounded-[3px] text-[12px]"
        >
    </div>


    <div>
        <label class="block text-[11px] font-medium text-[#59636e] mb-2">
            Status
        </label>

        <select
            name="status"
            class="w-full h-[38px] px-3 border border-[#dce1e6] rounded-[3px] text-[12px]"
        >
            <option value="active" @selected(old('status', $ratePlan->status ?? 'active') === 'active')>
                Active
            </option>

            <option value="inactive" @selected(old('status', $ratePlan->status ?? 'active') === 'inactive')>
                Inactive
            </option>
        </select>
    </div>


    <div class="md:col-span-2">
        <label class="inline-flex items-center gap-2 text-[11px] text-[#59636e]">

            <input
                type="hidden"
                name="stop_sell"
                value="0"
            >

            <input
                type="checkbox"
                name="stop_sell"
                value="1"
                @checked(old('stop_sell', $ratePlan->stop_sell ?? false))
            >

            Stop Sell

        </label>
    </div>

</div>


@if($errors->any())

    <div class="mt-5 px-4 py-3 bg-red-50 border border-red-200 text-red-600 text-[11px]">

        @foreach($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach

    </div>

@endif