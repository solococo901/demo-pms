@extends('layouts.pms')

@section('title', 'Dashboard - CityHouse PMS')

@section('content')

<div>

    {{-- ========================================================= --}}
    {{-- PAGE HEADER --}}
    {{-- ========================================================= --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-5">

        <div>

            <h1 class="text-[18px] font-medium text-[#303641]">
                Dashboard
            </h1>

            <p class="text-[11px] text-[#8a949e] mt-1">
                {{ $property?->name ?? 'CityHouse Demo Hotel' }}
            </p>

        </div>


        <div class="flex items-center gap-2">

            <button
                class="h-[34px] px-4 bg-white border border-[#dce1e6] rounded-[3px] text-[11px] text-[#5e6975] hover:border-[#1677ff]"
            >
                ↻ Refresh
            </button>

            <button
                class="h-[34px] px-4 bg-[#1677ff] text-white rounded-[3px] text-[11px] font-medium hover:bg-[#0969da]"
            >
                + New Booking
            </button>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- PROPERTY INFO --}}
    {{-- ========================================================= --}}
    <div class="bg-white border border-[#e2e6ea] rounded-[3px] mb-5">

        <div class="px-5 py-4 border-b border-[#edf0f2]">

            <div class="text-[12px] font-medium text-[#39434d]">
                Property Overview
            </div>

        </div>


        <div class="grid grid-cols-2 lg:grid-cols-5 divide-x divide-[#edf0f2]">

            <div class="px-5 py-4">

                <div class="text-[10px] uppercase tracking-wide text-[#929ba4]">
                    Property
                </div>

                <div class="text-[12px] font-medium mt-2">
                    {{ $property?->name ?? 'CityHouse Demo Hotel' }}
                </div>

            </div>


            <div class="px-5 py-4">

                <div class="text-[10px] uppercase tracking-wide text-[#929ba4]">
                    Code
                </div>

                <div class="text-[12px] font-medium mt-2">
                    {{ $property?->code ?? 'PROP_001' }}
                </div>

            </div>


            <div class="px-5 py-4">

                <div class="text-[10px] uppercase tracking-wide text-[#929ba4]">
                    Currency
                </div>

                <div class="text-[12px] font-medium mt-2">
                    {{ $property?->currency ?? 'VND' }}
                </div>

            </div>


            <div class="px-5 py-4">

                <div class="text-[10px] uppercase tracking-wide text-[#929ba4]">
                    Check-in
                </div>

                <div class="text-[12px] font-medium mt-2">
                    {{ $property?->check_in_time ?? '14:00' }}
                </div>

            </div>


            <div class="px-5 py-4">

                <div class="text-[10px] uppercase tracking-wide text-[#929ba4]">
                    Status
                </div>

                <div class="mt-2">

                    <span class="inline-flex items-center gap-2 text-[11px] text-[#31845b]">

                        <span class="w-[6px] h-[6px] rounded-full bg-[#3fb76f]"></span>

                        Active

                    </span>

                </div>

            </div>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- STATS --}}
    {{-- ========================================================= --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-5">

        <div class="bg-white border border-[#e2e6ea] rounded-[3px] px-5 py-5">

            <div class="text-[10px] uppercase tracking-wide text-[#9099a2]">
                Total Rooms
            </div>

            <div class="text-[24px] font-medium text-[#303641] mt-2">
                0
            </div>

            <div class="text-[10px] text-[#a0a8b0] mt-1">
                Rooms configured
            </div>

        </div>


        <div class="bg-white border border-[#e2e6ea] rounded-[3px] px-5 py-5">

            <div class="text-[10px] uppercase tracking-wide text-[#9099a2]">
                Available
            </div>

            <div class="text-[24px] font-medium text-[#303641] mt-2">
                0
            </div>

            <div class="text-[10px] text-[#a0a8b0] mt-1">
                Available today
            </div>

        </div>


        <div class="bg-white border border-[#e2e6ea] rounded-[3px] px-5 py-5">

            <div class="text-[10px] uppercase tracking-wide text-[#9099a2]">
                Arrivals
            </div>

            <div class="text-[24px] font-medium text-[#303641] mt-2">
                0
            </div>

            <div class="text-[10px] text-[#a0a8b0] mt-1">
                Arrivals today
            </div>

        </div>


        <div class="bg-white border border-[#e2e6ea] rounded-[3px] px-5 py-5">

            <div class="text-[10px] uppercase tracking-wide text-[#9099a2]">
                Revenue
            </div>

            <div class="text-[22px] font-medium text-[#303641] mt-2">
                0 ₫
            </div>

            <div class="text-[10px] text-[#a0a8b0] mt-1">
                Revenue today
            </div>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- TABLE AREA --}}
    {{-- ========================================================= --}}
    <div class="grid xl:grid-cols-[1fr_330px] gap-5">

        {{-- RESERVATIONS --}}
        <div class="bg-white border border-[#e2e6ea] rounded-[3px]">

            <div class="px-5 py-4 border-b border-[#edf0f2] flex items-center justify-between">

                <div>

                    <div class="text-[12px] font-medium">
                        Recent Reservations
                    </div>

                    <div class="text-[10px] text-[#9aa2aa] mt-1">
                        Latest reservations created in PMS
                    </div>

                </div>

                <a
                    href="#"
                    class="text-[11px] text-[#1677ff] hover:underline"
                >
                    View all
                </a>

            </div>


            <div class="overflow-x-auto">

                <table class="w-full text-left">

                    <thead class="bg-[#fafbfc] border-b border-[#e7eaed]">

                    <tr class="text-[10px] uppercase tracking-wide text-[#828d98]">

                        <th class="px-5 py-3 font-medium">
                            Booking ID
                        </th>

                        <th class="px-5 py-3 font-medium">
                            Guest
                        </th>

                        <th class="px-5 py-3 font-medium">
                            Stay
                        </th>

                        <th class="px-5 py-3 font-medium">
                            Source
                        </th>

                        <th class="px-5 py-3 font-medium">
                            Status
                        </th>

                    </tr>

                    </thead>


                    <tbody>

                    <tr>

                        <td
                            colspan="5"
                            class="px-5 py-14 text-center"
                        >

                            <div class="text-[12px] text-[#697581]">
                                No reservations yet
                            </div>

                            <div class="text-[10px] text-[#a0a8b0] mt-1">
                                Reservations will appear here.
                            </div>

                        </td>

                    </tr>

                    </tbody>

                </table>

            </div>

        </div>


        {{-- CHANNEX --}}
        <div class="bg-white border border-[#e2e6ea] rounded-[3px]">

            <div class="px-5 py-4 border-b border-[#edf0f2]">

                <div class="text-[12px] font-medium">
                    Channex
                </div>

                <div class="text-[10px] text-[#9aa2aa] mt-1">
                    Channel Manager integration
                </div>

            </div>


            <div class="divide-y divide-[#edf0f2]">

                <div class="px-5 py-4 flex items-center justify-between">

                    <span class="text-[11px] text-[#6e7882]">
                        API Connection
                    </span>

                    <span class="flex items-center gap-2 text-[10px] text-[#a06c2d]">

                        <span class="w-[6px] h-[6px] rounded-full bg-[#e6a34b]"></span>

                        Not connected

                    </span>

                </div>


                <div class="px-5 py-4 flex items-center justify-between">

                    <span class="text-[11px] text-[#6e7882]">
                        Property Mapping
                    </span>

                    <span class="text-[10px] text-[#98a1aa]">
                        Pending
                    </span>

                </div>


                <div class="px-5 py-4 flex items-center justify-between">

                    <span class="text-[11px] text-[#6e7882]">
                        Inventory Sync
                    </span>

                    <span class="text-[10px] text-[#98a1aa]">
                        Pending
                    </span>

                </div>


                <div class="px-5 py-4 flex items-center justify-between">

                    <span class="text-[11px] text-[#6e7882]">
                        Reservations
                    </span>

                    <span class="text-[10px] text-[#98a1aa]">
                        Pending
                    </span>

                </div>

            </div>


            <div class="p-4">

                <button
                    class="w-full h-[34px] border border-[#dce1e6] rounded-[3px] text-[11px] text-[#58636e] hover:border-[#1677ff] hover:text-[#1677ff]"
                >
                    Configure Channex
                </button>

            </div>

        </div>

    </div>

</div>

@endsection