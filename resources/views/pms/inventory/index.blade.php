@extends('layouts.pms')

@section('title', 'Inventory - CityHouse PMS')

@section('content')

<div>

    {{-- HEADER --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-5">

        <div>

            <h1 class="text-[18px] font-medium text-[#303641]">
                Inventory
            </h1>

            <p class="text-[11px] text-[#8a949e] mt-1">
                Availability by Room Type
            </p>

        </div>


        <div class="flex items-center gap-2">

            <a
                href="{{ route('pms.inventory.index', [
                    'start' => $startDate->subDays(7)->format('Y-m-d')
                ]) }}"
                class="h-[34px] px-3 inline-flex items-center bg-white border border-[#dce1e6] rounded-[3px] text-[11px]"
            >
                ← Previous
            </a>


            <a
                href="{{ route('pms.inventory.index') }}"
                class="h-[34px] px-3 inline-flex items-center bg-white border border-[#dce1e6] rounded-[3px] text-[11px]"
            >
                Today
            </a>


            <a
                href="{{ route('pms.inventory.index', [
                    'start' => $startDate->addDays(7)->format('Y-m-d')
                ]) }}"
                class="h-[34px] px-3 inline-flex items-center bg-white border border-[#dce1e6] rounded-[3px] text-[11px]"
            >
                Next →
            </a>

        </div>

    </div>


    {{-- MESSAGES --}}
    @if(session('success'))

        <div class="mb-4 px-4 py-3 bg-[#edf9f1] border border-[#cfe9d7] text-[#34754c] text-[11px] rounded-[3px]">
            {{ session('success') }}
        </div>

    @endif


    @if(session('error'))

        <div class="mb-4 px-4 py-3 bg-[#fff5f5] border border-[#f0d1d1] text-[#b34d4d] text-[11px] rounded-[3px]">
            {{ session('error') }}
        </div>

    @endif


    {{-- CONNECTION INFO --}}
    <div class="bg-white border border-[#e2e6ea] rounded-[3px] mb-5">

        <div class="grid sm:grid-cols-3 divide-x divide-[#edf0f2]">

            <div class="px-5 py-4">

                <div class="text-[10px] uppercase text-[#9099a2]">
                    Property
                </div>

                <div class="text-[12px] font-medium mt-2">
                    {{ $property->name }}
                </div>

            </div>


            <div class="px-5 py-4">

                <div class="text-[10px] uppercase text-[#9099a2]">
                    Channex
                </div>

                <div class="mt-2">

                    @if($property->channex_property_id)

                        <span class="inline-flex items-center gap-2 text-[10px] text-[#31845b]">

                            <span class="w-[6px] h-[6px] bg-[#3fb76f] rounded-full"></span>

                            Property Mapped

                        </span>

                    @else

                        <span class="text-[10px] text-[#b36b3e]">
                            Not mapped
                        </span>

                    @endif

                </div>

            </div>


            <div class="px-5 py-4">

                <div class="text-[10px] uppercase text-[#9099a2]">
                    Date Range
                </div>

                <div class="text-[12px] font-medium mt-2">

                    {{ $dates->first()->format('d/m/Y') }}

                    →

                    {{ $dates->last()->format('d/m/Y') }}

                </div>

            </div>

        </div>

    </div>


    <form
        method="POST"
        action="{{ route('pms.inventory.update') }}"
    >

        @csrf

        <input
            type="hidden"
            name="start_date"
            value="{{ $startDate->format('Y-m-d') }}"
        >


        <div class="bg-white border border-[#e2e6ea] rounded-[3px]">

            <div class="overflow-x-auto">

                <table class="w-full min-w-[1000px]">

                    <thead class="bg-[#fafbfc]">

                        <tr class="border-b border-[#e7eaed]">

                            <th class="sticky left-0 bg-[#fafbfc] z-10 w-[220px] px-5 py-3 text-left">

                                <span class="text-[10px] uppercase tracking-wide text-[#77828d] font-medium">
                                    Room Type
                                </span>

                            </th>


                            @foreach($dates as $date)

                                <th class="min-w-[115px] px-3 py-3 text-center">

                                    <div class="text-[10px] uppercase text-[#89939d] font-medium">

                                        {{ $date->format('D') }}

                                    </div>

                                    <div class="text-[12px] text-[#3d4751] mt-1">

                                        {{ $date->format('d/m') }}

                                    </div>

                                </th>

                            @endforeach

                        </tr>

                    </thead>


                    <tbody class="divide-y divide-[#edf0f2]">

                        @forelse($roomTypes as $roomType)

                            <tr class="hover:bg-[#fafcff]">

                                {{-- ROOM TYPE --}}
                                <td class="sticky left-0 bg-white z-10 px-5 py-4">

                                    <div class="text-[12px] font-medium text-[#36414c]">
                                        {{ $roomType->name }}
                                    </div>

                                    <div class="text-[10px] text-[#929ba4] mt-1">

                                        {{ $roomType->total_rooms }}
                                        total rooms

                                    </div>


                                    <div class="mt-2">

                                        @if($roomType->channex_room_type_id)

                                            <span class="inline-flex items-center gap-1 text-[9px] text-[#31845b]">

                                                <span class="w-[5px] h-[5px] bg-[#3fb76f] rounded-full"></span>

                                                Channex mapped

                                            </span>

                                        @else

                                            <span class="inline-flex items-center gap-1 text-[9px] text-[#a27a40]">

                                                <span class="w-[5px] h-[5px] bg-[#e1a34b] rounded-full"></span>

                                                Not mapped

                                            </span>

                                        @endif

                                    </div>

                                </td>


                                {{-- DAYS --}}
                                @foreach($dates as $date)

                                    @php

                                        $key =
                                            $roomType->id
                                            . '_'
                                            . $date->format('Y-m-d');

                                        $inventory =
                                            $inventories->get($key);

                                    @endphp


                                    <td class="px-3 py-4 text-center">

                                        <input
                                            type="number"
                                            min="0"
                                            max="{{ $roomType->total_rooms }}"
                                            name="availability[{{ $roomType->id }}][{{ $date->format('Y-m-d') }}]"
                                            value="{{ $inventory?->availability ?? $roomType->total_rooms }}"
                                            class="w-[72px] h-[34px] text-center border border-[#dce1e6] rounded-[3px] text-[12px] outline-none focus:border-[#1677ff]"
                                        >


                                        <div class="mt-2">

                                            @if($inventory?->sync_status === 'synced')

                                                <span class="text-[9px] text-[#31845b]">
                                                    Synced
                                                </span>

                                            @elseif($inventory?->sync_status === 'failed')

                                                <span class="text-[9px] text-[#c34e4e]">
                                                    Failed
                                                </span>

                                            @elseif($inventory?->sync_status === 'warning')

                                                <span class="text-[9px] text-[#ad772d]">
                                                    Warning
                                                </span>

                                            @else

                                                <span class="text-[9px] text-[#a0a8b0]">
                                                    Pending
                                                </span>

                                            @endif

                                        </div>

                                    </td>

                                @endforeach

                            </tr>


                        @empty

                            <tr>

                                <td
                                    colspan="8"
                                    class="py-16 text-center text-[11px] text-[#929ba4]"
                                >
                                    No Room Types found.
                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>


            <div class="px-5 py-4 border-t border-[#edf0f2] flex items-center justify-between">

                <div class="text-[10px] text-[#929ba4]">
                    Save will update PMS inventory and sync mapped Room Types to Channex.
                </div>


                <button
                    type="submit"
                    class="h-[34px] px-5 bg-[#1677ff] text-white rounded-[3px] text-[11px] font-medium hover:bg-[#0969da]"
                >
                    Save & Sync
                </button>

            </div>

        </div>

    </form>

</div>

@endsection