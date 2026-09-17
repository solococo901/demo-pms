<div class="grid md:grid-cols-2 gap-5">

    {{-- NAME --}}
    <div>

        <label class="block text-[11px] font-medium text-[#59636e] mb-2">
            Room Type Name
        </label>

        <input
            type="text"
            name="name"
            value="{{ old('name', $roomType->name ?? '') }}"
            placeholder="Example: Deluxe"
            class="w-full h-[38px] px-3 bg-white border border-[#dce1e6] rounded-[3px] text-[12px] outline-none focus:border-[#1677ff]"
        >

        @error('name')
        <div class="text-[10px] text-red-500 mt-1">
            {{ $message }}
        </div>
        @enderror

    </div>


    {{-- CODE --}}
    <div>

        <label class="block text-[11px] font-medium text-[#59636e] mb-2">
            Code
        </label>

        <input
            type="text"
            name="code"
            value="{{ old('code', $roomType->code ?? '') }}"
            placeholder="Example: DLX"
            class="w-full h-[38px] px-3 bg-white border border-[#dce1e6] rounded-[3px] text-[12px] uppercase outline-none focus:border-[#1677ff]"
        >

        @error('code')
        <div class="text-[10px] text-red-500 mt-1">
            {{ $message }}
        </div>
        @enderror

    </div>


    {{-- TOTAL ROOMS --}}
    <div>

        <label class="block text-[11px] font-medium text-[#59636e] mb-2">
            Total Rooms
        </label>

        <input
            type="number"
            min="1"
            name="total_rooms"
            value="{{ old('total_rooms', $roomType->total_rooms ?? 1) }}"
            class="w-full h-[38px] px-3 bg-white border border-[#dce1e6] rounded-[3px] text-[12px] outline-none focus:border-[#1677ff]"
        >

    </div>


    {{-- BASE PRICE --}}
    <div>

        <label class="block text-[11px] font-medium text-[#59636e] mb-2">
            Base Rate
        </label>

        <input
            type="number"
            min="0"
            name="base_price"
            value="{{ old('base_price', $roomType->base_price ?? 0) }}"
            class="w-full h-[38px] px-3 bg-white border border-[#dce1e6] rounded-[3px] text-[12px] outline-none focus:border-[#1677ff]"
        >

    </div>


    {{-- ADULT --}}
    <div>

        <label class="block text-[11px] font-medium text-[#59636e] mb-2">
            Max Adults
        </label>

        <input
            type="number"
            min="1"
            name="max_adults"
            value="{{ old('max_adults', $roomType->max_adults ?? 2) }}"
            class="w-full h-[38px] px-3 bg-white border border-[#dce1e6] rounded-[3px] text-[12px] outline-none focus:border-[#1677ff]"
        >

    </div>


    {{-- CHILD --}}
    <div>

        <label class="block text-[11px] font-medium text-[#59636e] mb-2">
            Max Children
        </label>

        <input
            type="number"
            min="0"
            name="max_children"
            value="{{ old('max_children', $roomType->max_children ?? 0) }}"
            class="w-full h-[38px] px-3 bg-white border border-[#dce1e6] rounded-[3px] text-[12px] outline-none focus:border-[#1677ff]"
        >

    </div>


    {{-- STATUS --}}
    <div>

        <label class="block text-[11px] font-medium text-[#59636e] mb-2">
            Status
        </label>

        <select
            name="status"
            class="w-full h-[38px] px-3 bg-white border border-[#dce1e6] rounded-[3px] text-[12px] outline-none focus:border-[#1677ff]"
        >

            <option
                value="active"
                @selected(
                    old(
                        'status',
                        $roomType->status ?? 'active'
                    ) === 'active'
                )
            >
                Active
            </option>

            <option
                value="inactive"
                @selected(
                    old(
                        'status',
                        $roomType->status ?? 'active'
                    ) === 'inactive'
                )
            >
                Inactive
            </option>

        </select>

    </div>


    {{-- DESCRIPTION --}}
    <div class="md:col-span-2">

        <label class="block text-[11px] font-medium text-[#59636e] mb-2">
            Description
        </label>

        <textarea
            name="description"
            rows="4"
            placeholder="Room type description..."
            class="w-full px-3 py-3 bg-white border border-[#dce1e6] rounded-[3px] text-[12px] outline-none resize-none focus:border-[#1677ff]"
        >{{ old('description', $roomType->description ?? '') }}</textarea>

    </div>

</div>