@extends('layouts.pms')

@section('title', 'Edit Room - CityHouse PMS')

@section('content')

<div class="max-w-[980px]">

    {{-- ====================================================== --}}
    {{-- HEADER --}}
    {{-- ====================================================== --}}

    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between mb-5">

        <div>

            <div class="flex items-center gap-3">

                <h1 class="text-[18px] font-medium text-[#303942]">
                    Room {{ $room->room_number }}
                </h1>


                @if($room->status === 'available')

                    <span class="px-2 py-1 bg-[#edf9f1] text-[#31845b] rounded-[3px] text-[9px]">
                        Available
                    </span>

                @elseif($room->status === 'occupied')

                    <span class="px-2 py-1 bg-[#eef5ff] text-[#1677ff] rounded-[3px] text-[9px]">
                        Occupied
                    </span>

                @elseif($room->status === 'maintenance')

                    <span class="px-2 py-1 bg-[#fff7e8] text-[#9a6c28] rounded-[3px] text-[9px]">
                        Maintenance
                    </span>

                @else

                    <span class="px-2 py-1 bg-[#fff0f0] text-[#b64646] rounded-[3px] text-[9px]">
                        Out of Order
                    </span>

                @endif

            </div>


            <p class="text-[11px] text-[#8a949e] mt-1">
                {{ $room->roomType?->name ?? 'Room' }}
                @if($room->floor)
                    · Floor {{ $room->floor }}
                @endif
            </p>

        </div>


        <a
            href="{{ route('pms.rooms.index') }}"
            class="h-[34px] px-4 inline-flex items-center border border-[#dce1e6] bg-white rounded-[3px] text-[11px] text-[#5f6a75]"
        >
            ← Back
        </a>

    </div>


    {{-- ====================================================== --}}
    {{-- FORM --}}
    {{-- ====================================================== --}}

    <form
        method="POST"
        action="{{ route('pms.rooms.update', $room) }}"
    >

        @csrf
        @method('PUT')


        <div class="bg-white border border-[#e2e6ea] rounded-[3px]">

            <div class="px-5 py-4 border-b border-[#edf0f2]">

                <div class="text-[12px] font-medium text-[#36414c]">
                    Room Information
                </div>

                <div class="text-[10px] text-[#929ba4] mt-1">
                    Update room, operational and housekeeping status
                </div>

            </div>


            <div class="p-5">

                @include('pms.rooms._form')

            </div>


            <div class="px-5 py-4 border-t border-[#edf0f2] bg-[#fafbfc] flex justify-end gap-2">

                <a
                    href="{{ route('pms.rooms.index') }}"
                    class="h-[34px] px-4 inline-flex items-center justify-center border border-[#dce1e6] bg-white rounded-[3px] text-[11px] text-[#5f6a75]"
                >
                    Cancel
                </a>


                <button
                    type="submit"
                    class="h-[34px] px-5 bg-[#1677ff] text-white rounded-[3px] text-[11px] font-medium"
                >
                    Save Changes
                </button>

            </div>

        </div>

    </form>

</div>

@endsection