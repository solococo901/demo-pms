@extends('layouts.pms')

@section('title', 'Rooms - CityHouse PMS')

@section('content')

<div class="w-full">

    {{-- ====================================================== --}}
    {{-- HEADER --}}
    {{-- ====================================================== --}}

    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between mb-5">

        <div>

            <h1 class="text-[18px] font-medium text-[#303942]">
                Rooms
            </h1>

            <p class="text-[11px] text-[#8a949e] mt-1">
                Manage physical rooms and housekeeping status
            </p>

        </div>


        <a
            href="{{ route('pms.rooms.create') }}"
            class="h-[34px] px-4 inline-flex items-center justify-center bg-[#1677ff] text-white rounded-[3px] text-[11px] font-medium hover:bg-[#0969da]"
        >
            + Add Room
        </a>

    </div>


    {{-- ====================================================== --}}
    {{-- MESSAGES --}}
    {{-- ====================================================== --}}

    @if(session('success'))

        <div class="mb-4 px-4 py-3 bg-[#edf9f1] border border-[#cfe9d7] rounded-[3px] text-[11px] text-[#34754c]">
            {{ session('success') }}
        </div>

    @endif


    @if($errors->any())

        <div class="mb-4 px-4 py-3 bg-[#fff3f3] border border-[#f1cccc] rounded-[3px]">

            @foreach($errors->all() as $error)

                <div class="text-[10px] text-[#b64646]">
                    {{ $error }}
                </div>

            @endforeach

        </div>

    @endif


    {{-- ====================================================== --}}
    {{-- PROPERTY SUMMARY --}}
    {{-- ====================================================== --}}

    <div class="bg-white border border-[#e2e6ea] rounded-[3px] mb-4">

        <div class="px-5 py-4 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">

            <div>

                <div class="text-[10px] uppercase tracking-wide text-[#98a1aa]">
                    Property
                </div>

                <div class="text-[12px] font-medium text-[#36414c] mt-1">
                    {{ $property->name }}
                </div>

            </div>


            <div class="flex items-center gap-6">

                <div>

                    <div class="text-[9px] uppercase tracking-wide text-[#98a1aa]">
                        Total Rooms
                    </div>

                    <div class="text-[16px] font-medium text-[#36414c] mt-1">
                        {{ $rooms->count() }}
                    </div>

                </div>


                <div>

                    <div class="text-[9px] uppercase tracking-wide text-[#98a1aa]">
                        Available
                    </div>

                    <div class="text-[16px] font-medium text-[#31845b] mt-1">
                        {{ $rooms->where('status', 'available')->count() }}
                    </div>

                </div>


                <div>

                    <div class="text-[9px] uppercase tracking-wide text-[#98a1aa]">
                        Occupied
                    </div>

                    <div class="text-[16px] font-medium text-[#1677ff] mt-1">
                        {{ $rooms->where('status', 'occupied')->count() }}
                    </div>

                </div>


                <div>

                    <div class="text-[9px] uppercase tracking-wide text-[#98a1aa]">
                        Dirty
                    </div>

                    <div class="text-[16px] font-medium text-[#b17a32] mt-1">
                        {{ $rooms->where('housekeeping_status', 'dirty')->count() }}
                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- ====================================================== --}}
    {{-- FILTERS --}}
    {{-- ====================================================== --}}

    <form
        method="GET"
        action="{{ route('pms.rooms.index') }}"
        class="bg-white border border-[#e2e6ea] rounded-[3px] mb-4"
    >

        <div class="p-4">

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-[1.4fr_1fr_1fr_1fr_auto] gap-3">


                {{-- SEARCH --}}
                <div>

                    <label class="block text-[9px] uppercase tracking-wide text-[#8c969f] mb-1.5">
                        Search
                    </label>

                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Room number or floor"
                        class="w-full h-[34px] px-3 border border-[#dce1e6] bg-white rounded-[3px] text-[11px] outline-none focus:border-[#1677ff]"
                    >

                </div>


                {{-- ROOM TYPE --}}
                <div>

                    <label class="block text-[9px] uppercase tracking-wide text-[#8c969f] mb-1.5">
                        Room Type
                    </label>

                    <select
                        name="room_type"
                        class="w-full h-[34px] px-3 border border-[#dce1e6] bg-white rounded-[3px] text-[11px] outline-none focus:border-[#1677ff]"
                    >

                        <option value="">
                            All Room Types
                        </option>

                        @foreach($roomTypes as $roomType)

                            <option
                                value="{{ $roomType->id }}"
                                @selected((string) request('room_type') === (string) $roomType->id)
                            >
                                {{ $roomType->name }}
                            </option>

                        @endforeach

                    </select>

                </div>


                {{-- ROOM STATUS --}}
                <div>

                    <label class="block text-[9px] uppercase tracking-wide text-[#8c969f] mb-1.5">
                        Room Status
                    </label>

                    <select
                        name="status"
                        class="w-full h-[34px] px-3 border border-[#dce1e6] bg-white rounded-[3px] text-[11px] outline-none focus:border-[#1677ff]"
                    >

                        <option value="">
                            All Status
                        </option>

                        <option value="available" @selected(request('status') === 'available')>
                            Available
                        </option>

                        <option value="occupied" @selected(request('status') === 'occupied')>
                            Occupied
                        </option>

                        <option value="maintenance" @selected(request('status') === 'maintenance')>
                            Maintenance
                        </option>

                        <option value="out_of_order" @selected(request('status') === 'out_of_order')>
                            Out of Order
                        </option>

                    </select>

                </div>


                {{-- HOUSEKEEPING --}}
                <div>

                    <label class="block text-[9px] uppercase tracking-wide text-[#8c969f] mb-1.5">
                        Housekeeping
                    </label>

                    <select
                        name="housekeeping_status"
                        class="w-full h-[34px] px-3 border border-[#dce1e6] bg-white rounded-[3px] text-[11px] outline-none focus:border-[#1677ff]"
                    >

                        <option value="">
                            All
                        </option>

                        <option value="clean" @selected(request('housekeeping_status') === 'clean')>
                            Clean
                        </option>

                        <option value="dirty" @selected(request('housekeeping_status') === 'dirty')>
                            Dirty
                        </option>

                        <option value="cleaning" @selected(request('housekeeping_status') === 'cleaning')>
                            Cleaning
                        </option>

                        <option value="inspected" @selected(request('housekeeping_status') === 'inspected')>
                            Inspected
                        </option>

                    </select>

                </div>


                {{-- BUTTONS --}}
                <div class="flex items-end gap-2">

                    <button
                        type="submit"
                        class="h-[34px] px-4 bg-[#1677ff] text-white rounded-[3px] text-[10px]"
                    >
                        Filter
                    </button>


                    <a
                        href="{{ route('pms.rooms.index') }}"
                        class="h-[34px] px-3 inline-flex items-center border border-[#dce1e6] rounded-[3px] text-[10px] text-[#65707b]"
                    >
                        Reset
                    </a>

                </div>

            </div>

        </div>

    </form>


    {{-- ====================================================== --}}
    {{-- ROOMS TABLE --}}
    {{-- ====================================================== --}}

    <div class="bg-white border border-[#e2e6ea] rounded-[3px] overflow-hidden">

        <div class="overflow-x-auto">

            <table class="w-full min-w-[950px]">

                <thead class="bg-[#fafbfc] border-b border-[#e7eaed]">

                    <tr class="text-left text-[9px] uppercase tracking-wide text-[#77828d]">

                        <th class="px-5 py-3 font-medium">
                            Room
                        </th>

                        <th class="px-5 py-3 font-medium">
                            Room Type
                        </th>

                        <th class="px-5 py-3 font-medium">
                            Floor
                        </th>

                        <th class="px-5 py-3 font-medium">
                            Room Status
                        </th>

                        <th class="px-5 py-3 font-medium">
                            Housekeeping
                        </th>

                        <th class="px-5 py-3 font-medium">
                            Notes
                        </th>

                        <th class="px-5 py-3 font-medium text-right">
                            Actions
                        </th>

                    </tr>

                </thead>


                <tbody class="divide-y divide-[#edf0f2]">

                    @forelse($rooms as $room)

                        <tr class="hover:bg-[#fafcff]">

                            {{-- ROOM --}}
                            <td class="px-5 py-4">

                                <div class="text-[13px] font-medium text-[#303942]">
                                    {{ $room->room_number }}
                                </div>

                                <div class="text-[9px] text-[#9ba4ad] mt-1">
                                    ID #{{ $room->id }}
                                </div>

                            </td>


                            {{-- ROOM TYPE --}}
                            <td class="px-5 py-4">

                                <div class="text-[11px] text-[#505c67]">
                                    {{ $room->roomType?->name ?? '-' }}
                                </div>

                                @if($room->roomType?->code)

                                    <div class="text-[9px] text-[#9ba4ad] mt-1">
                                        {{ $room->roomType->code }}
                                    </div>

                                @endif

                            </td>


                            {{-- FLOOR --}}
                            <td class="px-5 py-4 text-[11px] text-[#66717d]">
                                {{ $room->floor ?: '-' }}
                            </td>


                            {{-- ROOM STATUS --}}
                            <td class="px-5 py-4">

                                @if($room->status === 'available')

                                    <span class="inline-flex items-center gap-1.5 px-2 py-1 bg-[#edf9f1] text-[#31845b] rounded-[3px] text-[9px]">

                                        <span class="w-[5px] h-[5px] bg-[#3fb76f] rounded-full"></span>

                                        Available

                                    </span>

                                @elseif($room->status === 'occupied')

                                    <span class="inline-flex items-center gap-1.5 px-2 py-1 bg-[#eef5ff] text-[#1677ff] rounded-[3px] text-[9px]">

                                        <span class="w-[5px] h-[5px] bg-[#1677ff] rounded-full"></span>

                                        Occupied

                                    </span>

                                @elseif($room->status === 'maintenance')

                                    <span class="inline-flex items-center gap-1.5 px-2 py-1 bg-[#fff7e8] text-[#9a6c28] rounded-[3px] text-[9px]">

                                        <span class="w-[5px] h-[5px] bg-[#e2a64c] rounded-full"></span>

                                        Maintenance

                                    </span>

                                @else

                                    <span class="inline-flex items-center gap-1.5 px-2 py-1 bg-[#fff0f0] text-[#b64646] rounded-[3px] text-[9px]">

                                        <span class="w-[5px] h-[5px] bg-[#d95c5c] rounded-full"></span>

                                        Out of Order

                                    </span>

                                @endif

                            </td>


                            {{-- HOUSEKEEPING --}}
                            <td class="px-5 py-4">

                                @if($room->housekeeping_status === 'clean')

                                    <span class="text-[10px] text-[#31845b]">
                                        ● Clean
                                    </span>

                                @elseif($room->housekeeping_status === 'dirty')

                                    <span class="text-[10px] text-[#b64646]">
                                        ● Dirty
                                    </span>

                                @elseif($room->housekeeping_status === 'cleaning')

                                    <span class="text-[10px] text-[#b17a32]">
                                        ● Cleaning
                                    </span>

                                @else

                                    <span class="text-[10px] text-[#1677ff]">
                                        ● Inspected
                                    </span>

                                @endif

                            </td>


                            {{-- NOTES --}}
                            <td class="px-5 py-4">

                                <div class="max-w-[240px] truncate text-[10px] text-[#7b858f]">
                                    {{ $room->notes ?: '-' }}
                                </div>

                            </td>


                            {{-- ACTIONS --}}
                            <td class="px-5 py-4">

                                <div class="flex justify-end items-center gap-3">

                                    <a
                                        href="{{ route('pms.rooms.edit', $room) }}"
                                        class="text-[10px] text-[#1677ff] hover:underline"
                                    >
                                        Edit
                                    </a>


                                    <form
                                        method="POST"
                                        action="{{ route('pms.rooms.destroy', $room) }}"
                                        onsubmit="return confirm('Delete room {{ $room->room_number }}?')"
                                    >

                                        @csrf
                                        @method('DELETE')


                                        <button
                                            type="submit"
                                            class="text-[10px] text-[#c04b4b] hover:underline"
                                        >
                                            Delete
                                        </button>

                                    </form>

                                </div>

                            </td>

                        </tr>


                    @empty

                        <tr>

                            <td
                                colspan="7"
                                class="py-16 text-center"
                            >

                                <div class="text-[12px] text-[#76818c]">
                                    No rooms found
                                </div>

                                <div class="text-[10px] text-[#a0a8b0] mt-1">
                                    Create your first physical room.
                                </div>

                                <a
                                    href="{{ route('pms.rooms.create') }}"
                                    class="inline-flex mt-4 h-[32px] px-4 items-center bg-[#1677ff] text-white rounded-[3px] text-[10px]"
                                >
                                    + Add Room
                                </a>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        {{-- FOOTER --}}
        <div class="px-5 py-3 border-t border-[#edf0f2] bg-[#fafbfc] text-[9px] text-[#929ba4]">

            {{ $rooms->count() }}
            room(s) displayed

        </div>

    </div>

</div>

@endsection