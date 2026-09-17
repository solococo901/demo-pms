@extends('layouts.pms')

@section('title', 'Front Desk - CityHouse PMS')

@section('content')

@php
    $todayDate = \Carbon\CarbonImmutable::parse($today);
@endphp


<div class="w-full">

    {{-- ====================================================== --}}
    {{-- HEADER --}}
    {{-- ====================================================== --}}

    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between mb-5">

        <div>

            <h1 class="text-[18px] font-medium text-[#303942]">
                Front Desk
            </h1>

            <div class="text-[10px] text-[#929ba4] mt-1">

                {{ $property->name }}

                <span class="mx-1">
                    •
                </span>

                {{ $todayDate->format('d/m/Y') }}

            </div>

        </div>


        <div class="flex gap-2">

            <a
                href="{{ route('pms.reservations.create') }}"
                class="h-[34px] px-4 inline-flex items-center justify-center border border-[#dce1e6] bg-white rounded-[3px] text-[10px] text-[#59636e]"
            >
                + New Reservation
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


    {{-- ====================================================== --}}
    {{-- STATS --}}
    {{-- ====================================================== --}}

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">

        {{-- ARRIVALS --}}
        <div class="bg-white border border-[#e2e6ea] rounded-[3px] p-4">

            <div class="text-[9px] uppercase tracking-wide text-[#929ba4]">
                Today's Arrivals
            </div>

            <div class="text-[22px] font-medium text-[#1677ff] mt-2">
                {{ $arrivals->count() }}
            </div>

        </div>


        {{-- IN HOUSE --}}
        <div class="bg-white border border-[#e2e6ea] rounded-[3px] p-4">

            <div class="text-[9px] uppercase tracking-wide text-[#929ba4]">
                In House
            </div>

            <div class="text-[22px] font-medium text-[#31845b] mt-2">
                {{ $inHouse->count() }}
            </div>

        </div>


        {{-- DEPARTURES --}}
        <div class="bg-white border border-[#e2e6ea] rounded-[3px] p-4">

            <div class="text-[9px] uppercase tracking-wide text-[#929ba4]">
                Today's Departures
            </div>

            <div class="text-[22px] font-medium text-[#59636e] mt-2">
                {{ $departures->count() }}
            </div>

        </div>


        {{-- UNASSIGNED --}}
        <div class="bg-white border border-[#e2e6ea] rounded-[3px] p-4">

            <div class="text-[9px] uppercase tracking-wide text-[#929ba4]">
                Unassigned Arrivals
            </div>

            <div class="text-[22px] font-medium {{ $unassignedArrivals > 0 ? 'text-[#b64646]' : 'text-[#31845b]' }} mt-2">
                {{ $unassignedArrivals }}
            </div>

        </div>

    </div>


    {{-- ====================================================== --}}
    {{-- TODAY ARRIVALS --}}
    {{-- ====================================================== --}}

    <div class="bg-white border border-[#e2e6ea] rounded-[3px] overflow-hidden mb-4">

        <div class="px-5 py-4 border-b border-[#edf0f2] flex items-center justify-between">

            <div>

                <div class="text-[12px] font-medium text-[#36414c]">
                    Today's Arrivals
                </div>

                <div class="text-[9px] text-[#929ba4] mt-1">
                    Guests expected to arrive today
                </div>

            </div>


            <span class="px-2 py-1 bg-[#eef5ff] text-[#1677ff] rounded-[3px] text-[9px]">
                {{ $arrivals->count() }} Arrival(s)
            </span>

        </div>


        <div class="overflow-x-auto">

            <table class="w-full min-w-[1000px]">

                <thead class="bg-[#fafbfc] border-b border-[#edf0f2]">

                    <tr class="text-left text-[9px] uppercase tracking-wide text-[#77828d]">

                        <th class="px-5 py-3 font-medium">
                            Reservation
                        </th>

                        <th class="px-5 py-3 font-medium">
                            Guest
                        </th>

                        <th class="px-5 py-3 font-medium">
                            Stay
                        </th>

                        <th class="px-5 py-3 font-medium">
                            Room Type
                        </th>

                        <th class="px-5 py-3 font-medium">
                            Physical Room
                        </th>

                        <th class="px-5 py-3 font-medium">
                            Payment
                        </th>

                        <th class="px-5 py-3 font-medium text-right">
                            Action
                        </th>

                    </tr>

                </thead>


                <tbody class="divide-y divide-[#edf0f2]">

                    @forelse($arrivals as $reservation)

                        @php
                            $roomLine = $reservation->rooms->first();
                        @endphp


                        <tr class="hover:bg-[#fafcff]">

                            {{-- RESERVATION --}}
                            <td class="px-5 py-4">

                                <a
                                    href="{{ route('pms.reservations.show', $reservation) }}"
                                    class="text-[10px] font-medium text-[#1677ff]"
                                >
                                    {{ $reservation->code }}
                                </a>

                                <div class="text-[9px] text-[#929ba4] mt-1">
                                    {{ ucwords(str_replace('_', ' ', $reservation->source)) }}
                                </div>

                            </td>


                            {{-- GUEST --}}
                            <td class="px-5 py-4">

                                <div class="text-[11px] font-medium text-[#36414c]">
                                    {{ $reservation->guest?->full_name ?? '-' }}
                                </div>

                                <div class="text-[9px] text-[#929ba4] mt-1">
                                    {{ $reservation->guest?->phone ?? '-' }}
                                </div>

                            </td>


                            {{-- STAY --}}
                            <td class="px-5 py-4">

                                @if($roomLine)

                                    <div class="text-[10px] text-[#59636e]">
                                        {{ \Carbon\CarbonImmutable::parse($roomLine->check_in)->format('d/m') }}
                                        →
                                        {{ \Carbon\CarbonImmutable::parse($roomLine->check_out)->format('d/m') }}
                                    </div>

                                @else

                                    <span class="text-[10px] text-[#929ba4]">
                                        -
                                    </span>

                                @endif

                            </td>


                            {{-- TYPE --}}
                            <td class="px-5 py-4">

                                <div class="text-[10px] text-[#59636e]">
                                    {{ $roomLine?->roomType?->name ?? '-' }}
                                </div>

                            </td>


                            {{-- PHYSICAL ROOM --}}
                            <td class="px-5 py-4">

                                @if($roomLine?->room)

                                    <div class="text-[11px] font-medium text-[#36414c]">
                                        Room {{ $roomLine->room->room_number }}
                                    </div>


                                    @if(in_array($roomLine->room->housekeeping_status, ['clean', 'inspected'], true))

                                        <div class="text-[9px] text-[#31845b] mt-1">
                                            ● {{ ucfirst($roomLine->room->housekeeping_status) }}
                                        </div>

                                    @else

                                        <div class="text-[9px] text-[#b64646] mt-1">
                                            ● {{ ucfirst($roomLine->room->housekeeping_status) }}
                                        </div>

                                    @endif

                                @else

                                    <a
                                        href="{{ route('pms.reservations.show', $reservation) }}"
                                        class="inline-flex px-2 py-1 bg-[#fff7e8] text-[#9a6c28] rounded-[3px] text-[9px]"
                                    >
                                        Assign Room
                                    </a>

                                @endif

                            </td>


                            {{-- PAYMENT --}}
                            <td class="px-5 py-4">

                                @if($reservation->payment_status === 'paid')

                                    <span class="text-[9px] text-[#31845b]">
                                        ● Paid
                                    </span>

                                @elseif($reservation->payment_status === 'partial')

                                    <span class="text-[9px] text-[#9a6c28]">
                                        ● Partial
                                    </span>

                                @else

                                    <span class="text-[9px] text-[#b64646]">
                                        ● Unpaid
                                    </span>

                                @endif

                            </td>


                            {{-- ACTION --}}
                            <td class="px-5 py-4">

                                <div class="flex justify-end">

                                    @if(!$roomLine?->room)

                                        <a
                                            href="{{ route('pms.reservations.show', $reservation) }}"
                                            class="h-[30px] px-3 inline-flex items-center border border-[#dce1e6] rounded-[3px] text-[9px] text-[#59636e]"
                                        >
                                            Assign First
                                        </a>

                                    @elseif(!in_array($roomLine->room->housekeeping_status, ['clean', 'inspected'], true))

                                        <span class="h-[30px] px-3 inline-flex items-center bg-[#fff7e8] text-[#9a6c28] rounded-[3px] text-[9px]">
                                            Room Not Ready
                                        </span>

                                    @else

                                        <form
                                            method="POST"
                                            action="{{ route('pms.front-desk.check-in', $reservation) }}"
                                            onsubmit="return confirm('Check in {{ $reservation->guest?->full_name }} to Room {{ $roomLine->room->room_number }}?')"
                                        >

                                            @csrf


                                            <button
                                                type="submit"
                                                class="h-[30px] px-4 bg-[#1677ff] text-white rounded-[3px] text-[9px] font-medium"
                                            >
                                                Check In
                                            </button>

                                        </form>

                                    @endif

                                </div>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="7" class="py-12 text-center text-[10px] text-[#929ba4]">
                                No arrivals today.
                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>


    {{-- ====================================================== --}}
    {{-- IN HOUSE --}}
    {{-- ====================================================== --}}

    <div class="bg-white border border-[#e2e6ea] rounded-[3px] overflow-hidden mb-4">

        <div class="px-5 py-4 border-b border-[#edf0f2] flex items-center justify-between">

            <div>

                <div class="text-[12px] font-medium text-[#36414c]">
                    In House
                </div>

                <div class="text-[9px] text-[#929ba4] mt-1">
                    Guests currently staying in the hotel
                </div>

            </div>


            <span class="px-2 py-1 bg-[#edf9f1] text-[#31845b] rounded-[3px] text-[9px]">
                {{ $inHouse->count() }} Guest(s)
            </span>

        </div>


        <div class="overflow-x-auto">

            <table class="w-full min-w-[900px]">

                <thead class="bg-[#fafbfc] border-b border-[#edf0f2]">

                    <tr class="text-left text-[9px] uppercase tracking-wide text-[#77828d]">

                        <th class="px-5 py-3 font-medium">
                            Room
                        </th>

                        <th class="px-5 py-3 font-medium">
                            Guest
                        </th>

                        <th class="px-5 py-3 font-medium">
                            Reservation
                        </th>

                        <th class="px-5 py-3 font-medium">
                            Departure
                        </th>

                        <th class="px-5 py-3 font-medium">
                            Balance
                        </th>

                        <th class="px-5 py-3 font-medium text-right">
                            Action
                        </th>

                    </tr>

                </thead>


                <tbody class="divide-y divide-[#edf0f2]">

                    @forelse($inHouse as $reservation)

                        @php
                            $roomLine = $reservation->rooms->first();

                            $balance = max(
                                0,
                                (float) $reservation->total_amount
                                -
                                (float) $reservation->paid_amount
                            );
                        @endphp


                        <tr class="hover:bg-[#fafcff]">

                            {{-- ROOM --}}
                            <td class="px-5 py-4">

                                @if($roomLine?->room)

                                    <div class="text-[15px] font-medium text-[#1677ff]">
                                        {{ $roomLine->room->room_number }}
                                    </div>

                                    <div class="text-[9px] text-[#929ba4] mt-1">
                                        {{ $roomLine->roomType?->name }}
                                    </div>

                                @else

                                    <span class="text-[10px] text-[#b64646]">
                                        No Room
                                    </span>

                                @endif

                            </td>


                            {{-- GUEST --}}
                            <td class="px-5 py-4">

                                <div class="text-[11px] font-medium text-[#36414c]">
                                    {{ $reservation->guest?->full_name ?? '-' }}
                                </div>

                                <div class="text-[9px] text-[#929ba4] mt-1">
                                    {{ $reservation->guest?->phone ?? '-' }}
                                </div>

                            </td>


                            {{-- RES --}}
                            <td class="px-5 py-4">

                                <a
                                    href="{{ route('pms.reservations.show', $reservation) }}"
                                    class="text-[10px] text-[#1677ff]"
                                >
                                    {{ $reservation->code }}
                                </a>

                                @if($reservation->checked_in_at)

                                    <div class="text-[9px] text-[#929ba4] mt-1">
                                        In:
                                        {{ $reservation->checked_in_at->format('d/m H:i') }}
                                    </div>

                                @endif

                            </td>


                            {{-- DEPARTURE --}}
                            <td class="px-5 py-4">

                                @if($roomLine)

                                    <div class="text-[10px] text-[#59636e]">
                                        {{ \Carbon\CarbonImmutable::parse($roomLine->check_out)->format('d/m/Y') }}
                                    </div>

                                @else

                                    -

                                @endif

                            </td>


                            {{-- BALANCE --}}
                            <td class="px-5 py-4">

                                <div class="text-[10px] font-medium {{ $balance > 0 ? 'text-[#b64646]' : 'text-[#31845b]' }}">
                                    {{ number_format($balance, 0, ',', '.') }} ₫
                                </div>

                            </td>


                            {{-- ACTION --}}
                            <td class="px-5 py-4">

                                <div class="flex justify-end">

                                    <form
                                        method="POST"
                                        action="{{ route('pms.front-desk.check-out', $reservation) }}"
                                        onsubmit="return confirm('Check out {{ $reservation->guest?->full_name }} from Room {{ $roomLine?->room?->room_number }}?')"
                                    >

                                        @csrf


                                        <button
                                            type="submit"
                                            class="h-[30px] px-4 border border-[#dce1e6] bg-white text-[#59636e] rounded-[3px] text-[9px] font-medium hover:bg-[#fafbfc]"
                                        >
                                            Check Out
                                        </button>

                                    </form>

                                </div>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="6" class="py-12 text-center text-[10px] text-[#929ba4]">
                                No guests currently in house.
                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>


    {{-- ====================================================== --}}
    {{-- DEPARTURES --}}
    {{-- ====================================================== --}}

    <div class="bg-white border border-[#e2e6ea] rounded-[3px] overflow-hidden">

        <div class="px-5 py-4 border-b border-[#edf0f2]">

            <div class="text-[12px] font-medium text-[#36414c]">
                Today's Departures
            </div>

            <div class="text-[9px] text-[#929ba4] mt-1">
                Guests scheduled to leave today
            </div>

        </div>


        <div class="overflow-x-auto">

            <table class="w-full min-w-[850px]">

                <thead class="bg-[#fafbfc] border-b border-[#edf0f2]">

                    <tr class="text-left text-[9px] uppercase tracking-wide text-[#77828d]">

                        <th class="px-5 py-3 font-medium">
                            Room
                        </th>

                        <th class="px-5 py-3 font-medium">
                            Guest
                        </th>

                        <th class="px-5 py-3 font-medium">
                            Reservation
                        </th>

                        <th class="px-5 py-3 font-medium">
                            Payment
                        </th>

                        <th class="px-5 py-3 font-medium">
                            Status
                        </th>

                    </tr>

                </thead>


                <tbody class="divide-y divide-[#edf0f2]">

                    @forelse($departures as $reservation)

                        @php
                            $roomLine = $reservation->rooms->first();
                        @endphp


                        <tr>

                            <td class="px-5 py-4 text-[12px] font-medium text-[#36414c]">
                                {{ $roomLine?->room?->room_number ?? '-' }}
                            </td>


                            <td class="px-5 py-4">

                                <div class="text-[10px] text-[#36414c]">
                                    {{ $reservation->guest?->full_name ?? '-' }}
                                </div>

                            </td>


                            <td class="px-5 py-4">

                                <a
                                    href="{{ route('pms.reservations.show', $reservation) }}"
                                    class="text-[10px] text-[#1677ff]"
                                >
                                    {{ $reservation->code }}
                                </a>

                            </td>


                            <td class="px-5 py-4">

                                <span class="text-[9px] {{ $reservation->payment_status === 'paid' ? 'text-[#31845b]' : 'text-[#b64646]' }}">
                                    {{ ucfirst($reservation->payment_status) }}
                                </span>

                            </td>


                            <td class="px-5 py-4">

                                @if($reservation->status === 'checked_out')

                                    <span class="px-2 py-1 bg-[#f3f4f5] text-[#68737e] rounded-[3px] text-[9px]">
                                        Checked Out
                                    </span>

                                @else

                                    <span class="px-2 py-1 bg-[#fff7e8] text-[#9a6c28] rounded-[3px] text-[9px]">
                                        Due Out
                                    </span>

                                @endif

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="5" class="py-12 text-center text-[10px] text-[#929ba4]">
                                No departures today.
                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

</div>

@endsection