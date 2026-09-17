<div class="grid grid-cols-1 md:grid-cols-2 gap-5">

    {{-- ====================================================== --}}
    {{-- ROOM NUMBER --}}
    {{-- ====================================================== --}}

    <div>

        <label class="block text-[10px] font-medium text-[#59636e] mb-2">
            Room Number
            <span class="text-red-500">*</span>
        </label>

        <input
            type="text"
            name="room_number"
            value="{{ old('room_number', $room->room_number ?? '') }}"
            placeholder="Example: 101"
            class="w-full h-[38px] px-3 border border-[#dce1e6] rounded-[3px] text-[12px] outline-none focus:border-[#1677ff]"
        >

        <div class="text-[9px] text-[#9aa2aa] mt-1.5">
            Physical room number used by hotel operations.
        </div>

    </div>


    {{-- ====================================================== --}}
    {{-- ROOM TYPE --}}
    {{-- ====================================================== --}}

    <div>

        <label class="block text-[10px] font-medium text-[#59636e] mb-2">
            Room Type
            <span class="text-red-500">*</span>
        </label>

        <select
            name="room_type_id"
            class="w-full h-[38px] px-3 bg-white border border-[#dce1e6] rounded-[3px] text-[12px] outline-none focus:border-[#1677ff]"
        >

            <option value="">
                Select Room Type
            </option>

            @foreach($roomTypes as $roomType)

                <option
                    value="{{ $roomType->id }}"
                    @selected((string) old('room_type_id', $room->room_type_id ?? '') === (string) $roomType->id)
                >
                    {{ $roomType->name }} — {{ $roomType->code }}
                </option>

            @endforeach

        </select>

    </div>


    {{-- ====================================================== --}}
    {{-- FLOOR --}}
    {{-- ====================================================== --}}

    <div>

        <label class="block text-[10px] font-medium text-[#59636e] mb-2">
            Floor
        </label>

        <input
            type="text"
            name="floor"
            value="{{ old('floor', $room->floor ?? '') }}"
            placeholder="Example: 1"
            class="w-full h-[38px] px-3 border border-[#dce1e6] rounded-[3px] text-[12px] outline-none focus:border-[#1677ff]"
        >

    </div>


    {{-- ====================================================== --}}
    {{-- ROOM STATUS --}}
    {{-- ====================================================== --}}

    <div>

        <label class="block text-[10px] font-medium text-[#59636e] mb-2">
            Room Status
            <span class="text-red-500">*</span>
        </label>

        <select
            name="status"
            class="w-full h-[38px] px-3 bg-white border border-[#dce1e6] rounded-[3px] text-[12px] outline-none focus:border-[#1677ff]"
        >

            <option
                value="available"
                @selected(old('status', $room->status ?? 'available') === 'available')
            >
                Available
            </option>

            <option
                value="occupied"
                @selected(old('status', $room->status ?? 'available') === 'occupied')
            >
                Occupied
            </option>

            <option
                value="maintenance"
                @selected(old('status', $room->status ?? 'available') === 'maintenance')
            >
                Maintenance
            </option>

            <option
                value="out_of_order"
                @selected(old('status', $room->status ?? 'available') === 'out_of_order')
            >
                Out of Order
            </option>

        </select>


        <div class="text-[9px] text-[#9aa2aa] mt-1.5">

            Operational status of this physical room.

        </div>

    </div>


    {{-- ====================================================== --}}
    {{-- HOUSEKEEPING --}}
    {{-- ====================================================== --}}

    <div>

        <label class="block text-[10px] font-medium text-[#59636e] mb-2">
            Housekeeping Status
            <span class="text-red-500">*</span>
        </label>

        <select
            name="housekeeping_status"
            class="w-full h-[38px] px-3 bg-white border border-[#dce1e6] rounded-[3px] text-[12px] outline-none focus:border-[#1677ff]"
        >

            <option
                value="clean"
                @selected(old('housekeeping_status', $room->housekeeping_status ?? 'clean') === 'clean')
            >
                Clean
            </option>

            <option
                value="dirty"
                @selected(old('housekeeping_status', $room->housekeeping_status ?? 'clean') === 'dirty')
            >
                Dirty
            </option>

            <option
                value="cleaning"
                @selected(old('housekeeping_status', $room->housekeeping_status ?? 'clean') === 'cleaning')
            >
                Cleaning
            </option>

            <option
                value="inspected"
                @selected(old('housekeeping_status', $room->housekeeping_status ?? 'clean') === 'inspected')
            >
                Inspected
            </option>

        </select>

    </div>


    {{-- EMPTY COLUMN --}}
    <div></div>


    {{-- ====================================================== --}}
    {{-- NOTES --}}
    {{-- ====================================================== --}}

    <div class="md:col-span-2">

        <label class="block text-[10px] font-medium text-[#59636e] mb-2">
            Internal Notes
        </label>

        <textarea
            name="notes"
            rows="4"
            placeholder="Example: Corner room, near elevator..."
            class="w-full px-3 py-3 border border-[#dce1e6] rounded-[3px] text-[12px] outline-none resize-y focus:border-[#1677ff]"
        >{{ old('notes', $room->notes ?? '') }}</textarea>

        <div class="text-[9px] text-[#9aa2aa] mt-1.5">
            Internal notes are not visible to guests.
        </div>

    </div>

</div>


{{-- ====================================================== --}}
{{-- VALIDATION ERRORS --}}
{{-- ====================================================== --}}

@if($errors->any())

    <div class="mt-5 px-4 py-3 bg-[#fff3f3] border border-[#f1cccc] rounded-[3px]">

        <div class="text-[10px] font-medium text-[#b64646] mb-1">
            Please check the following:
        </div>

        @foreach($errors->all() as $error)

            <div class="text-[10px] text-[#b64646]">
                {{ $error }}
            </div>

        @endforeach

    </div>

@endif