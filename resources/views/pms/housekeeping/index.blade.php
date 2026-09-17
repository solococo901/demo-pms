@extends('layouts.pms')

@section('title', 'Housekeeping - CityHouse PMS')

@section('content')

<div class="w-full">

    {{-- ====================================================== --}}
    {{-- HEADER --}}
    {{-- ====================================================== --}}

    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between mb-5">

        <div>

            <h1 class="text-[18px] font-medium text-[#303942]">
                Housekeeping
            </h1>

            <div class="text-[10px] text-[#929ba4] mt-1">
                {{ $property->name }}
                ·
                Room cleaning and readiness board
            </div>

        </div>


        <div class="flex gap-2">

            <a
                href="{{ route('pms.front-desk.index') }}"
                class="h-[34px] px-4 inline-flex items-center justify-center border border-[#dce1e6] bg-white rounded-[3px] text-[10px] text-[#59636e]"
            >
                Front Desk
            </a>


            <a
                href="{{ route('pms.rooms.index') }}"
                class="h-[34px] px-4 inline-flex items-center justify-center bg-[#1677ff] text-white rounded-[3px] text-[10px]"
            >
                Rooms
            </a>

        </div>

    </div>


    {{-- ====================================================== --}}
    {{-- MESSAGES --}}
    {{-- ====================================================== --}}

    @if(session('success'))

        <div class="mb-4 px-4 py-3 bg-[#edf9f1] border border-[#cfe9d7] rounded-[3px] text-[11px] text-[#34754c]">
            {{ session('success') }}
        </div>

    @endif


    @if(session('error'))

        <div class="mb-4 px-4 py-3 bg-[#fff3f3] border border-[#f1cccc] rounded-[3px] text-[11px] text-[#b64646]">
            {{ session('error') }}
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
    {{-- STATS --}}
    {{-- ====================================================== --}}

    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-5">

        {{-- TOTAL --}}
        <div class="bg-white border border-[#e2e6ea] rounded-[3px] p-4">

            <div class="text-[9px] uppercase tracking-wide text-[#929ba4]">
                Total Rooms
            </div>

            <div class="text-[22px] font-medium text-[#36414c] mt-2">
                {{ $stats['total'] }}
            </div>

        </div>


        {{-- DIRTY --}}
        <div class="bg-white border border-[#e2e6ea] rounded-[3px] p-4">

            <div class="text-[9px] uppercase tracking-wide text-[#929ba4]">
                Dirty
            </div>

            <div class="text-[22px] font-medium text-[#b64646] mt-2">
                {{ $stats['dirty'] }}
            </div>

        </div>


        {{-- CLEANING --}}
        <div class="bg-white border border-[#e2e6ea] rounded-[3px] p-4">

            <div class="text-[9px] uppercase tracking-wide text-[#929ba4]">
                Cleaning
            </div>

            <div class="text-[22px] font-medium text-[#b17a32] mt-2">
                {{ $stats['cleaning'] }}
            </div>

        </div>


        {{-- CLEAN --}}
        <div class="bg-white border border-[#e2e6ea] rounded-[3px] p-4">

            <div class="text-[9px] uppercase tracking-wide text-[#929ba4]">
                Clean
            </div>

            <div class="text-[22px] font-medium text-[#31845b] mt-2">
                {{ $stats['clean'] }}
            </div>

        </div>


        {{-- INSPECTED --}}
        <div class="bg-white border border-[#e2e6ea] rounded-[3px] p-4">

            <div class="text-[9px] uppercase tracking-wide text-[#929ba4]">
                Inspected
            </div>

            <div class="text-[22px] font-medium text-[#1677ff] mt-2">
                {{ $stats['inspected'] }}
            </div>

        </div>

    </div>


    {{-- ====================================================== --}}
    {{-- FILTERS --}}
    {{-- ====================================================== --}}

    <form
        method="GET"
        action="{{ route('pms.housekeeping.index') }}"
        class="bg-white border border-[#e2e6ea] rounded-[3px] mb-4"
    >

        <div class="p-4">

            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-[1.5fr_1fr_1fr_1fr_auto] gap-3">

                {{-- SEARCH --}}
                <div>

                    <label class="block text-[9px] uppercase tracking-wide text-[#8c969f] mb-1.5">
                        Search
                    </label>

                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Room number, type, floor..."
                        class="w-full h-[34px] px-3 border border-[#dce1e6] bg-white rounded-[3px] text-[11px] outline-none focus:border-[#1677ff]"
                    >

                </div>


                {{-- HOUSEKEEPING --}}
                <div>

                    <label class="block text-[9px] uppercase tracking-wide text-[#8c969f] mb-1.5">
                        Housekeeping
                    </label>

                    <select
                        name="housekeeping_status"
                        class="w-full h-[34px] px-3 border border-[#dce1e6] bg-white rounded-[3px] text-[11px]"
                    >

                        <option value="">
                            All Status
                        </option>

                        <option value="dirty" @selected(request('housekeeping_status') === 'dirty')>
                            Dirty
                        </option>

                        <option value="cleaning" @selected(request('housekeeping_status') === 'cleaning')>
                            Cleaning
                        </option>

                        <option value="clean" @selected(request('housekeeping_status') === 'clean')>
                            Clean
                        </option>

                        <option value="inspected" @selected(request('housekeeping_status') === 'inspected')>
                            Inspected
                        </option>

                    </select>

                </div>


                {{-- ROOM STATUS --}}
                <div>

                    <label class="block text-[9px] uppercase tracking-wide text-[#8c969f] mb-1.5">
                        Room Status
                    </label>

                    <select
                        name="status"
                        class="w-full h-[34px] px-3 border border-[#dce1e6] bg-white rounded-[3px] text-[11px]"
                    >

                        <option value="">
                            All Room Status
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


                {{-- FLOOR --}}
                <div>

                    <label class="block text-[9px] uppercase tracking-wide text-[#8c969f] mb-1.5">
                        Floor
                    </label>

                    <select
                        name="floor"
                        class="w-full h-[34px] px-3 border border-[#dce1e6] bg-white rounded-[3px] text-[11px]"
                    >

                        <option value="">
                            All Floors
                        </option>

                        @foreach($floors as $floor)

                            <option
                                value="{{ $floor }}"
                                @selected((string) request('floor') === (string) $floor)
                            >
                                Floor {{ $floor }}
                            </option>

                        @endforeach

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
                        href="{{ route('pms.housekeeping.index') }}"
                        class="h-[34px] px-3 inline-flex items-center border border-[#dce1e6] rounded-[3px] text-[10px] text-[#65707b]"
                    >
                        Reset
                    </a>

                </div>

            </div>

        </div>

    </form>


    {{-- ====================================================== --}}
    {{-- ROOM BOARD --}}
    {{-- ====================================================== --}}

    <div class="bg-white border border-[#e2e6ea] rounded-[3px] overflow-hidden">

        <div class="px-5 py-4 border-b border-[#edf0f2] flex items-center justify-between">

            <div>

                <div class="text-[12px] font-medium text-[#36414c]">
                    Room Board
                </div>

                <div class="text-[9px] text-[#929ba4] mt-1">
                    Update cleaning progress and room readiness
                </div>

            </div>


            <div class="text-[9px] text-[#929ba4]">
                {{ $rooms->count() }} room(s)
            </div>

        </div>


        @if($rooms->isEmpty())

            <div class="py-16 text-center">

                <div class="text-[11px] text-[#76818c]">
                    No rooms found.
                </div>

            </div>

        @else

            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">

                @foreach($rooms as $room)

                    <div class="p-4 border-b border-r border-[#edf0f2]">

                        {{-- ROOM HEADER --}}
                        <div class="flex items-start justify-between gap-3">

                            <div>

                                <div class="flex items-center gap-2">

                                    <span class="text-[17px] font-medium text-[#303942]">
                                        {{ $room->room_number }}
                                    </span>


                                    {{-- OPERATIONAL STATUS --}}
                                    @if($room->status === 'occupied')

                                        <span class="px-2 py-1 bg-[#eef5ff] text-[#1677ff] rounded-[3px] text-[8px]">
                                            Occupied
                                        </span>

                                    @elseif($room->status === 'maintenance')

                                        <span class="px-2 py-1 bg-[#fff7e8] text-[#9a6c28] rounded-[3px] text-[8px]">
                                            Maintenance
                                        </span>

                                    @elseif($room->status === 'out_of_order')

                                        <span class="px-2 py-1 bg-[#fff0f0] text-[#b64646] rounded-[3px] text-[8px]">
                                            Out of Order
                                        </span>

                                    @else

                                        <span class="px-2 py-1 bg-[#edf9f1] text-[#31845b] rounded-[3px] text-[8px]">
                                            Available
                                        </span>

                                    @endif

                                </div>


                                <div class="text-[9px] text-[#929ba4] mt-1">

                                    {{ $room->roomType?->name ?? '-' }}

                                    @if($room->floor)
                                        · Floor {{ $room->floor }}
                                    @endif

                                </div>

                            </div>


                            {{-- HOUSEKEEPING STATUS --}}
                            <div>

                                @if($room->housekeeping_status === 'dirty')

                                    <span class="inline-flex items-center gap-1 px-2 py-1 bg-[#fff0f0] text-[#b64646] rounded-[3px] text-[9px]">
                                        <span class="w-[5px] h-[5px] rounded-full bg-[#d95c5c]"></span>
                                        Dirty
                                    </span>

                                @elseif($room->housekeeping_status === 'cleaning')

                                    <span class="inline-flex items-center gap-1 px-2 py-1 bg-[#fff7e8] text-[#9a6c28] rounded-[3px] text-[9px]">
                                        <span class="w-[5px] h-[5px] rounded-full bg-[#d8a348]"></span>
                                        Cleaning
                                    </span>

                                @elseif($room->housekeeping_status === 'clean')

                                    <span class="inline-flex items-center gap-1 px-2 py-1 bg-[#edf9f1] text-[#31845b] rounded-[3px] text-[9px]">
                                        <span class="w-[5px] h-[5px] rounded-full bg-[#3fb76f]"></span>
                                        Clean
                                    </span>

                                @else

                                    <span class="inline-flex items-center gap-1 px-2 py-1 bg-[#eef5ff] text-[#1677ff] rounded-[3px] text-[9px]">
                                        <span class="w-[5px] h-[5px] rounded-full bg-[#1677ff]"></span>
                                        Inspected
                                    </span>

                                @endif

                            </div>

                        </div>


                        {{-- NOTES --}}
                        @if($room->notes)

                            <div class="mt-3 text-[9px] text-[#7c8791] line-clamp-2">
                                {{ $room->notes }}
                            </div>

                        @endif


                        {{-- STATUS CHANGE --}}
                        <div class="mt-4 pt-4 border-t border-[#edf0f2]">

                            <form
                                method="POST"
                                action="{{ route('pms.housekeeping.update-status', $room) }}"
                            >

                                @csrf
                                @method('PATCH')


                                <div class="flex gap-2">

                                    <select
                                        name="housekeeping_status"
                                        class="flex-1 h-[32px] px-2 border border-[#dce1e6] bg-white rounded-[3px] text-[9px] outline-none focus:border-[#1677ff]"
                                    >

                                        <option
                                            value="dirty"
                                            @selected($room->housekeeping_status === 'dirty')
                                        >
                                            Dirty
                                        </option>

                                        <option
                                            value="cleaning"
                                            @selected($room->housekeeping_status === 'cleaning')
                                        >
                                            Cleaning
                                        </option>

                                        <option
                                            value="clean"
                                            @selected($room->housekeeping_status === 'clean')
                                        >
                                            Clean
                                        </option>

                                        <option
                                            value="inspected"
                                            @selected($room->housekeeping_status === 'inspected')
                                        >
                                            Inspected
                                        </option>

                                    </select>


                                    <button
                                        type="submit"
                                        class="h-[32px] px-3 bg-[#1677ff] text-white rounded-[3px] text-[9px]"
                                    >
                                        Update
                                    </button>

                                </div>

                            </form>

                        </div>


                        {{-- QUICK ACTIONS --}}
                        <div class="mt-2 grid grid-cols-4 gap-1">

                            <form
                                method="POST"
                                action="{{ route('pms.housekeeping.update-status', $room) }}"
                            >
                                @csrf
                                @method('PATCH')

                                <input
                                    type="hidden"
                                    name="housekeeping_status"
                                    value="dirty"
                                >

                                <button
                                    type="submit"
                                    class="w-full h-[27px] border border-[#f0caca] text-[#b64646] rounded-[3px] text-[8px] hover:bg-[#fff5f5]"
                                >
                                    Dirty
                                </button>

                            </form>


                            <form
                                method="POST"
                                action="{{ route('pms.housekeeping.update-status', $room) }}"
                            >
                                @csrf
                                @method('PATCH')

                                <input
                                    type="hidden"
                                    name="housekeeping_status"
                                    value="cleaning"
                                >

                                <button
                                    type="submit"
                                    class="w-full h-[27px] border border-[#eedcb9] text-[#9a6c28] rounded-[3px] text-[8px] hover:bg-[#fffaf1]"
                                >
                                    Cleaning
                                </button>

                            </form>


                            <form
                                method="POST"
                                action="{{ route('pms.housekeeping.update-status', $room) }}"
                            >
                                @csrf
                                @method('PATCH')

                                <input
                                    type="hidden"
                                    name="housekeeping_status"
                                    value="clean"
                                >

                                <button
                                    type="submit"
                                    class="w-full h-[27px] border border-[#cfe9d7] text-[#31845b] rounded-[3px] text-[8px] hover:bg-[#f6fcf8]"
                                >
                                    Clean
                                </button>

                            </form>


                            <form
                                method="POST"
                                action="{{ route('pms.housekeeping.update-status', $room) }}"
                            >
                                @csrf
                                @method('PATCH')

                                <input
                                    type="hidden"
                                    name="housekeeping_status"
                                    value="inspected"
                                >

                                <button
                                    type="submit"
                                    class="w-full h-[27px] border border-[#cfe1fa] text-[#1677ff] rounded-[3px] text-[8px] hover:bg-[#f5f9ff]"
                                >
                                    Inspect
                                </button>

                            </form>

                        </div>

                    </div>

                @endforeach

            </div>

        @endif

    </div>

</div>

@endsection