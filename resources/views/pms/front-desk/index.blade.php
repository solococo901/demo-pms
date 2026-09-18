@extends('layouts.pms')

@section('title', 'Front Desk - CityHouse PMS')

@section('content')

@php
    $timezone = $property->timezone ?? 'Asia/Ho_Chi_Minh';

    $todayLabel = \Carbon\CarbonImmutable::parse(
        $today,
        $timezone
    )->format('d/m/Y');

    $arrivalCount = $arrivals->count();
    $inHouseCount = $inHouse->count();
    $departureCount = $departures->count();

    $computedOutstandingInHouse = isset($outstandingInHouse)
        ? (int) $outstandingInHouse
        : $inHouse->filter(function ($reservation) {
            $balance = max(
                0,
                (float) $reservation->total_amount
                -
                (float) $reservation->paid_amount
            );

            return $balance > 0;
        })->count();
@endphp


<div class="w-full max-w-[1380px] mx-auto">

    {{-- ====================================================== --}}
    {{-- HEADER --}}
    {{-- ====================================================== --}}

    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between mb-5">

        <div>

            <h1 class="text-[18px] font-medium text-[#303942]">
                Front Desk
            </h1>

            <div class="text-[10px] text-[#929ba4] mt-1">
                Quản lý khách đến, khách đang lưu trú và khách trả phòng trong ngày
            </div>

            <div class="text-[9px] text-[#a0a8b0] mt-1">
                {{ $property->name }} · {{ $todayLabel }}
            </div>

        </div>


        <div class="flex flex-wrap gap-2">

            <a
                href="{{ route('pms.reservations.index') }}"
                class="h-[34px] px-4 inline-flex items-center justify-center border border-[#dce1e6] bg-white rounded-[3px] text-[10px] text-[#59636e]"
            >
                Reservations
            </a>

            <a
                href="{{ route('pms.housekeeping.index') }}"
                class="h-[34px] px-4 inline-flex items-center justify-center border border-[#dce1e6] bg-white rounded-[3px] text-[10px] text-[#59636e]"
            >
                Housekeeping
            </a>

        </div>

    </div>


    {{-- ====================================================== --}}
    {{-- FLASH --}}
    {{-- ====================================================== --}}

    @if(session('success'))

        <div class="mb-4 px-4 py-3 bg-[#edf9f1] border border-[#cfe9d7] rounded-[3px]">

            <div class="text-[11px] text-[#34754c]">
                {{ session('success') }}
            </div>

        </div>

    @endif


    @if(session('error'))

        <div class="mb-4 px-4 py-3 bg-[#fff3f3] border border-[#f1cccc] rounded-[3px]">

            <div class="text-[11px] text-[#b64646]">
                {{ session('error') }}
            </div>

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

    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-3 mb-5">

        {{-- ARRIVALS --}}
        <div class="bg-white border border-[#e2e6ea] rounded-[3px] p-4">

            <div class="text-[9px] uppercase tracking-wide text-[#929ba4]">
                Today's Arrivals
            </div>

            <div class="text-[8px] text-[#a0a8b0] mt-1">
                Khách dự kiến đến hôm nay
            </div>

            <div class="text-[22px] font-medium text-[#1677ff] mt-2">
                {{ $arrivalCount }}
            </div>

        </div>


        {{-- IN HOUSE --}}
        <div class="bg-white border border-[#e2e6ea] rounded-[3px] p-4">

            <div class="text-[9px] uppercase tracking-wide text-[#929ba4]">
                In House
            </div>

            <div class="text-[8px] text-[#a0a8b0] mt-1">
                Khách đang lưu trú
            </div>

            <div class="text-[22px] font-medium text-[#31845b] mt-2">
                {{ $inHouseCount }}
            </div>

        </div>


        {{-- DEPARTURES --}}
        <div class="bg-white border border-[#e2e6ea] rounded-[3px] p-4">

            <div class="text-[9px] uppercase tracking-wide text-[#929ba4]">
                Today's Departures
            </div>

            <div class="text-[8px] text-[#a0a8b0] mt-1">
                Khách dự kiến trả phòng hôm nay
            </div>

            <div class="text-[22px] font-medium text-[#59636e] mt-2">
                {{ $departureCount }}
            </div>

        </div>


        {{-- UNASSIGNED --}}
        <div class="bg-white border border-[#e2e6ea] rounded-[3px] p-4">

            <div class="text-[9px] uppercase tracking-wide text-[#929ba4]">
                Unassigned
            </div>

            <div class="text-[8px] text-[#a0a8b0] mt-1">
                Khách đến chưa được gán phòng
            </div>

            <div class="text-[22px] font-medium {{ $unassignedArrivals > 0 ? 'text-[#d17e15]' : 'text-[#31845b]' }} mt-2">
                {{ $unassignedArrivals }}
            </div>

        </div>


        {{-- OUTSTANDING --}}
        <div class="bg-white border border-[#e2e6ea] rounded-[3px] p-4">

            <div class="text-[9px] uppercase tracking-wide text-[#929ba4]">
                Outstanding
            </div>

            <div class="text-[8px] text-[#a0a8b0] mt-1">
                Khách đang lưu trú còn công nợ
            </div>

            <div class="text-[22px] font-medium {{ $computedOutstandingInHouse > 0 ? 'text-[#b64646]' : 'text-[#31845b]' }} mt-2">
                {{ $computedOutstandingInHouse }}
            </div>

        </div>

    </div>


    {{-- ====================================================== --}}
    {{-- TODAY'S ARRIVALS --}}
    {{-- ====================================================== --}}

    <div class="bg-white border border-[#e2e6ea] rounded-[3px] overflow-hidden mb-4">

        <div class="px-5 py-4 border-b border-[#edf0f2] flex items-start justify-between gap-4">

            <div>

                <div class="text-[12px] font-medium text-[#36414c]">
                    Today's Arrivals
                </div>

                <div class="text-[9px] text-[#929ba4] mt-1">
                    Danh sách khách dự kiến nhận phòng hôm nay
                </div>

            </div>


            <div class="text-right">

                <div class="text-[16px] font-medium text-[#1677ff]">
                    {{ $arrivalCount }}
                </div>

                <div class="text-[8px] text-[#929ba4]">
                    booking
                </div>

            </div>

        </div>


        @if($arrivals->isEmpty())

            <div class="py-12 px-5 text-center">

                <div class="text-[11px] text-[#7c8791]">
                    No arrivals today.
                </div>

                <div class="text-[9px] text-[#a0a8b0] mt-1">
                    Hôm nay chưa có khách dự kiến nhận phòng.
                </div>

            </div>

        @else

            <div class="overflow-x-auto">

                <table class="w-full min-w-[1050px]">

                    <thead class="bg-[#fafbfc] border-b border-[#edf0f2]">

                        <tr class="text-left">

                            <th class="px-5 py-3 text-[8px] uppercase tracking-wide font-medium text-[#929ba4]">
                                Reservation
                            </th>

                            <th class="px-4 py-3 text-[8px] uppercase tracking-wide font-medium text-[#929ba4]">
                                Guest
                            </th>

                            <th class="px-4 py-3 text-[8px] uppercase tracking-wide font-medium text-[#929ba4]">
                                Stay
                            </th>

                            <th class="px-4 py-3 text-[8px] uppercase tracking-wide font-medium text-[#929ba4]">
                                Room Type
                            </th>

                            <th class="px-4 py-3 text-[8px] uppercase tracking-wide font-medium text-[#929ba4]">
                                Physical Room
                            </th>

                            <th class="px-4 py-3 text-[8px] uppercase tracking-wide font-medium text-[#929ba4]">
                                Payment
                            </th>

                            <th class="px-5 py-3 text-[8px] uppercase tracking-wide font-medium text-[#929ba4] text-right">
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody class="divide-y divide-[#edf0f2]">

                        @foreach($arrivals as $reservation)

                            @php
                                $roomLine = $reservation->rooms->first();
                                $physicalRoom = $roomLine?->room;

                                $balance = max(
                                    0,
                                    (float) $reservation->total_amount
                                    -
                                    (float) $reservation->paid_amount
                                );
                            @endphp


                            <tr class="hover:bg-[#fcfdff] align-top">

                                {{-- RESERVATION --}}
                                <td class="px-5 py-4">

                                    <a
                                        href="{{ route('pms.reservations.show', $reservation) }}"
                                        class="text-[10px] font-medium text-[#1677ff] hover:underline"
                                    >
                                        {{ $reservation->code }}
                                    </a>

                                    <div class="mt-1">

                                        @if($reservation->status === 'confirmed')

                                            <span class="inline-flex px-2 py-1 bg-[#eef5ff] text-[#1677ff] rounded-[3px] text-[8px]">
                                                Confirmed
                                            </span>

                                        @else

                                            <span class="inline-flex px-2 py-1 bg-[#fff7e8] text-[#9a6c28] rounded-[3px] text-[8px]">
                                                {{ ucwords(str_replace('_', ' ', $reservation->status)) }}
                                            </span>

                                        @endif

                                    </div>

                                    <div class="text-[8px] text-[#929ba4] mt-1">
                                        {{ ucwords(str_replace('_', ' ', $reservation->source)) }}
                                    </div>

                                </td>


                                {{-- GUEST --}}
                                <td class="px-4 py-4">

                                    <div class="text-[10px] font-medium text-[#36414c]">
                                        {{ $reservation->guest?->full_name ?? 'No Guest' }}
                                    </div>

                                    <div class="text-[8px] text-[#929ba4] mt-1">
                                        {{ $reservation->guest?->phone ?: '-' }}
                                    </div>

                                </td>


                                {{-- STAY --}}
                                <td class="px-4 py-4">

                                    @if($roomLine)

                                        <div class="text-[10px] text-[#36414c]">
                                            {{ \Carbon\CarbonImmutable::parse($roomLine->check_in)->format('d/m/Y') }}
                                            →
                                            {{ \Carbon\CarbonImmutable::parse($roomLine->check_out)->format('d/m/Y') }}
                                        </div>

                                        <div class="text-[8px] text-[#929ba4] mt-1">
                                            {{ $roomLine->adults }} người lớn
                                            @if((int) $roomLine->children > 0)
                                                · {{ $roomLine->children }} trẻ em
                                            @endif
                                        </div>

                                    @else

                                        <span class="text-[9px] text-[#b64646]">
                                            Missing stay information
                                        </span>

                                    @endif

                                </td>


                                {{-- ROOM TYPE --}}
                                <td class="px-4 py-4">

                                    <div class="text-[10px] text-[#36414c]">
                                        {{ $roomLine?->roomType?->name ?? '-' }}
                                    </div>

                                    <div class="text-[8px] text-[#929ba4] mt-1">
                                        {{ $roomLine?->ratePlan?->name ?? 'No Rate Plan' }}
                                    </div>

                                </td>


                                {{-- PHYSICAL ROOM --}}
                                <td class="px-4 py-4">

                                    @if($physicalRoom)

                                        <div class="text-[13px] font-medium text-[#1677ff]">
                                            {{ $physicalRoom->room_number }}
                                        </div>

                                        <div class="flex flex-wrap gap-1 mt-1">

                                            <span class="px-1.5 py-0.5 bg-[#edf9f1] text-[#31845b] rounded-[3px] text-[7px]">
                                                {{ ucfirst($physicalRoom->housekeeping_status) }}
                                            </span>

                                            <span class="px-1.5 py-0.5 bg-[#f3f5f7] text-[#59636e] rounded-[3px] text-[7px]">
                                                {{ ucwords(str_replace('_', ' ', $physicalRoom->status)) }}
                                            </span>

                                        </div>

                                    @else

                                        <a
                                            href="{{ route('pms.reservations.show', $reservation) }}"
                                            class="inline-flex px-2 py-1 bg-[#fff7e8] text-[#9a6c28] rounded-[3px] text-[8px]"
                                        >
                                            Assign Room
                                        </a>

                                        <div class="text-[7px] text-[#a0a8b0] mt-1">
                                            Chưa gán phòng thực tế
                                        </div>

                                    @endif

                                </td>


                                {{-- PAYMENT --}}
                                <td class="px-4 py-4">

                                    @if($balance <= 0)

                                        <span class="inline-flex px-2 py-1 bg-[#edf9f1] text-[#31845b] rounded-[3px] text-[8px]">
                                            Paid
                                        </span>

                                        <div class="text-[8px] text-[#929ba4] mt-1">
                                            Đã thanh toán đủ
                                        </div>

                                    @else

                                        <div class="text-[10px] font-medium text-[#b64646]">
                                            {{ number_format($balance, 0, ',', '.') }} ₫
                                        </div>

                                        <div class="text-[8px] text-[#929ba4] mt-1">
                                            Công nợ còn lại
                                        </div>

                                    @endif

                                </td>


                                {{-- ACTION --}}
                                <td class="px-5 py-4">

                                    <div class="flex items-start justify-end gap-2">

                                        @if($physicalRoom)

                                            <form
                                                method="POST"
                                                action="{{ route('pms.front-desk.check-in', $reservation) }}"
                                                onsubmit="return confirm('Check in reservation {{ $reservation->code }}?')"
                                            >
                                                @csrf

                                                <button
                                                    type="submit"
                                                    class="h-[30px] px-3 bg-[#1677ff] text-white rounded-[3px] text-[9px]"
                                                >
                                                    Check In
                                                </button>

                                                <div class="text-[7px] text-[#929ba4] mt-1 text-center">
                                                    Nhận phòng
                                                </div>

                                            </form>

                                        @else

                                            <a
                                                href="{{ route('pms.reservations.show', $reservation) }}"
                                                class="h-[30px] px-3 inline-flex items-center justify-center border border-[#dce1e6] bg-white text-[#59636e] rounded-[3px] text-[9px]"
                                            >
                                                Assign Room
                                            </a>

                                        @endif


                                        <details class="relative">

                                            <summary
                                                class="list-none cursor-pointer h-[30px] px-3 inline-flex items-center justify-center border border-[#efcaca] bg-white text-[#b64646] rounded-[3px] text-[9px]"
                                            >
                                                No-show
                                            </summary>


                                            <div class="absolute right-0 z-30 mt-2 w-[290px] bg-white border border-[#e2e6ea] shadow-lg rounded-[3px] p-4 text-left">

                                                <div class="text-[10px] font-medium text-[#36414c]">
                                                    Mark as No-show
                                                </div>

                                                <div class="text-[8px] text-[#929ba4] mt-1">
                                                    Đánh dấu khách không đến nhận phòng
                                                </div>


                                                <form
                                                    method="POST"
                                                    action="{{ route('pms.reservations.no-show', $reservation) }}"
                                                    class="mt-3 space-y-3"
                                                    onsubmit="return confirm('Mark {{ $reservation->code }} as No-show?')"
                                                >
                                                    @csrf


                                                    <div>

                                                        <label class="block text-[8px] uppercase text-[#7f8993] mb-1">
                                                            Reason
                                                        </label>

                                                        <div class="text-[7px] text-[#a0a8b0] mb-1.5">
                                                            Lý do khách không đến
                                                        </div>

                                                        <input
                                                            type="text"
                                                            name="reason"
                                                            maxlength="255"
                                                            placeholder="Guest did not arrive..."
                                                            class="w-full h-[34px] px-3 border border-[#dce1e6] rounded-[3px] text-[9px]"
                                                        >

                                                    </div>


                                                    <div class="p-3 bg-[#fff7e8] border border-[#f0dfba] rounded-[3px] text-[8px] text-[#9a6c28] leading-4">
                                                        Inventory sẽ được trả lại và phòng vật lý sẽ được bỏ gán. Payment hiện tại không tự thay đổi.
                                                    </div>


                                                    <button
                                                        type="submit"
                                                        class="w-full h-[34px] bg-[#b64646] text-white rounded-[3px] text-[9px]"
                                                    >
                                                        Confirm No-show
                                                    </button>

                                                </form>

                                            </div>

                                        </details>

                                    </div>

                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>

        @endif

    </div>


    {{-- ====================================================== --}}
    {{-- IN HOUSE --}}
    {{-- ====================================================== --}}

    <div class="bg-white border border-[#e2e6ea] rounded-[3px] overflow-hidden mb-4">

        <div class="px-5 py-4 border-b border-[#edf0f2] flex items-start justify-between gap-4">

            <div>

                <div class="text-[12px] font-medium text-[#36414c]">
                    In House
                </div>

                <div class="text-[9px] text-[#929ba4] mt-1">
                    Danh sách khách đang lưu trú tại khách sạn
                </div>

            </div>


            <div class="text-right">

                <div class="text-[16px] font-medium text-[#31845b]">
                    {{ $inHouseCount }}
                </div>

                <div class="text-[8px] text-[#929ba4]">
                    booking
                </div>

            </div>

        </div>


        @if($inHouse->isEmpty())

            <div class="py-12 px-5 text-center">

                <div class="text-[11px] text-[#7c8791]">
                    No in-house guests.
                </div>

                <div class="text-[9px] text-[#a0a8b0] mt-1">
                    Hiện chưa có khách đang lưu trú.
                </div>

            </div>

        @else

            <div class="overflow-x-auto">

                <table class="w-full min-w-[1050px]">

                    <thead class="bg-[#fafbfc] border-b border-[#edf0f2]">

                        <tr class="text-left">

                            <th class="px-5 py-3 text-[8px] uppercase tracking-wide font-medium text-[#929ba4]">
                                Reservation
                            </th>

                            <th class="px-4 py-3 text-[8px] uppercase tracking-wide font-medium text-[#929ba4]">
                                Guest
                            </th>

                            <th class="px-4 py-3 text-[8px] uppercase tracking-wide font-medium text-[#929ba4]">
                                Room
                            </th>

                            <th class="px-4 py-3 text-[8px] uppercase tracking-wide font-medium text-[#929ba4]">
                                Departure
                            </th>

                            <th class="px-4 py-3 text-[8px] uppercase tracking-wide font-medium text-[#929ba4]">
                                Balance
                            </th>

                            <th class="px-5 py-3 text-[8px] uppercase tracking-wide font-medium text-[#929ba4] text-right">
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody class="divide-y divide-[#edf0f2]">

                        @foreach($inHouse as $reservation)

                            @php
                                $roomLine = $reservation->rooms->first();
                                $physicalRoom = $roomLine?->room;

                                $reservationBalance = max(
                                    0,
                                    (float) $reservation->total_amount
                                    -
                                    (float) $reservation->paid_amount
                                );
                            @endphp


                            <tr class="hover:bg-[#fcfdff] align-top">

                                {{-- RESERVATION --}}
                                <td class="px-5 py-4">

                                    <a
                                        href="{{ route('pms.reservations.show', $reservation) }}"
                                        class="text-[10px] font-medium text-[#1677ff] hover:underline"
                                    >
                                        {{ $reservation->code }}
                                    </a>

                                    <div class="mt-1">

                                        <span class="inline-flex px-2 py-1 bg-[#edf9f1] text-[#31845b] rounded-[3px] text-[8px]">
                                            Checked In
                                        </span>

                                    </div>

                                </td>


                                {{-- GUEST --}}
                                <td class="px-4 py-4">

                                    <div class="text-[10px] font-medium text-[#36414c]">
                                        {{ $reservation->guest?->full_name ?? 'No Guest' }}
                                    </div>

                                    <div class="text-[8px] text-[#929ba4] mt-1">
                                        {{ $reservation->guest?->phone ?: '-' }}
                                    </div>

                                </td>


                                {{-- ROOM --}}
                                <td class="px-4 py-4">

                                    @if($physicalRoom)

                                        <div class="text-[15px] font-medium text-[#1677ff]">
                                            {{ $physicalRoom->room_number }}
                                        </div>

                                        <div class="text-[8px] text-[#929ba4] mt-1">
                                            {{ $roomLine?->roomType?->name ?? '-' }}
                                        </div>

                                        <div class="flex gap-1 mt-1">

                                            <span class="px-1.5 py-0.5 bg-[#eef5ff] text-[#1677ff] rounded-[3px] text-[7px]">
                                                Occupied
                                            </span>

                                            <span class="px-1.5 py-0.5 bg-[#f3f5f7] text-[#59636e] rounded-[3px] text-[7px]">
                                                {{ ucfirst($physicalRoom->housekeeping_status) }}
                                            </span>

                                        </div>

                                    @else

                                        <span class="text-[9px] text-[#b64646]">
                                            Missing room
                                        </span>

                                    @endif

                                </td>


                                {{-- DEPARTURE --}}
                                <td class="px-4 py-4">

                                    @if($roomLine)

                                        <div class="text-[10px] text-[#36414c]">
                                            {{ \Carbon\CarbonImmutable::parse($roomLine->check_out)->format('d/m/Y') }}
                                        </div>

                                        <div class="text-[8px] text-[#929ba4] mt-1">
                                            {{ $property->check_out_time ?? '12:00' }}
                                        </div>

                                    @else

                                        -

                                    @endif

                                </td>


                                {{-- BALANCE --}}
                                <td class="px-4 py-4">

                                    @if($reservationBalance > 0)

                                        <div class="text-[11px] font-medium text-[#b64646]">
                                            {{ number_format($reservationBalance, 0, ',', '.') }} ₫
                                        </div>

                                        <div class="text-[8px] text-[#929ba4] mt-1">
                                            Công nợ còn lại
                                        </div>

                                    @else

                                        <span class="inline-flex px-2 py-1 bg-[#edf9f1] text-[#31845b] rounded-[3px] text-[8px]">
                                            Paid
                                        </span>

                                        <div class="text-[8px] text-[#929ba4] mt-1">
                                            Đủ điều kiện thanh toán
                                        </div>

                                    @endif

                                </td>


                                {{-- ACTION --}}
                                <td class="px-5 py-4">

                                    <div class="flex items-start justify-end gap-2">

                                        @if($physicalRoom)

                                            <a
                                                href="{{ route('pms.room-moves.create', $reservation) }}"
                                                class="h-[30px] px-3 inline-flex items-center justify-center border border-[#cfe1fa] bg-[#f7faff] text-[#1677ff] rounded-[3px] text-[9px]"
                                            >
                                                Room Move
                                            </a>

                                        @endif


                                        @if($reservationBalance > 0)

                                            <div class="text-right">

                                                <a
                                                    href="{{ route('pms.reservations.show', $reservation) }}"
                                                    class="h-[30px] px-3 inline-flex items-center justify-center bg-[#fff7e8] border border-[#f0dfba] text-[#9a6c28] rounded-[3px] text-[9px]"
                                                >
                                                    Complete Payment
                                                </a>

                                                <div class="text-[7px] text-[#929ba4] mt-1 text-center">
                                                    Hoàn tất thanh toán
                                                </div>

                                            </div>

                                        @else

                                            <div class="text-right">

                                                <form
                                                    method="POST"
                                                    action="{{ route('pms.front-desk.check-out', $reservation) }}"
                                                    onsubmit="return confirm('Check out reservation {{ $reservation->code }}?')"
                                                >
                                                    @csrf

                                                    <button
                                                        type="submit"
                                                        class="h-[30px] px-4 bg-[#1677ff] text-white rounded-[3px] text-[9px]"
                                                    >
                                                        Check Out
                                                    </button>

                                                </form>

                                                <div class="text-[7px] text-[#929ba4] mt-1 text-center">
                                                    Trả phòng
                                                </div>

                                            </div>

                                        @endif

                                    </div>

                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>

        @endif

    </div>


    {{-- ====================================================== --}}
    {{-- TODAY'S DEPARTURES --}}
    {{-- ====================================================== --}}

    <div class="bg-white border border-[#e2e6ea] rounded-[3px] overflow-hidden">

        <div class="px-5 py-4 border-b border-[#edf0f2] flex items-start justify-between gap-4">

            <div>

                <div class="text-[12px] font-medium text-[#36414c]">
                    Today's Departures
                </div>

                <div class="text-[9px] text-[#929ba4] mt-1">
                    Danh sách khách dự kiến hoặc đã trả phòng hôm nay
                </div>

            </div>


            <div class="text-right">

                <div class="text-[16px] font-medium text-[#59636e]">
                    {{ $departureCount }}
                </div>

                <div class="text-[8px] text-[#929ba4]">
                    booking
                </div>

            </div>

        </div>


        @if($departures->isEmpty())

            <div class="py-12 px-5 text-center">

                <div class="text-[11px] text-[#7c8791]">
                    No departures today.
                </div>

                <div class="text-[9px] text-[#a0a8b0] mt-1">
                    Hôm nay chưa có khách dự kiến trả phòng.
                </div>

            </div>

        @else

            <div class="overflow-x-auto">

                <table class="w-full min-w-[950px]">

                    <thead class="bg-[#fafbfc] border-b border-[#edf0f2]">

                        <tr class="text-left">

                            <th class="px-5 py-3 text-[8px] uppercase tracking-wide font-medium text-[#929ba4]">
                                Reservation
                            </th>

                            <th class="px-4 py-3 text-[8px] uppercase tracking-wide font-medium text-[#929ba4]">
                                Guest
                            </th>

                            <th class="px-4 py-3 text-[8px] uppercase tracking-wide font-medium text-[#929ba4]">
                                Room
                            </th>

                            <th class="px-4 py-3 text-[8px] uppercase tracking-wide font-medium text-[#929ba4]">
                                Payment
                            </th>

                            <th class="px-4 py-3 text-[8px] uppercase tracking-wide font-medium text-[#929ba4]">
                                Status
                            </th>

                            <th class="px-5 py-3 text-[8px] uppercase tracking-wide font-medium text-[#929ba4] text-right">
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody class="divide-y divide-[#edf0f2]">

                        @foreach($departures as $reservation)

                            @php
                                $roomLine = $reservation->rooms->first();
                                $physicalRoom = $roomLine?->room;

                                $departureBalance = max(
                                    0,
                                    (float) $reservation->total_amount
                                    -
                                    (float) $reservation->paid_amount
                                );
                            @endphp


                            <tr class="hover:bg-[#fcfdff] align-top">

                                <td class="px-5 py-4">

                                    <a
                                        href="{{ route('pms.reservations.show', $reservation) }}"
                                        class="text-[10px] font-medium text-[#1677ff] hover:underline"
                                    >
                                        {{ $reservation->code }}
                                    </a>

                                </td>


                                <td class="px-4 py-4">

                                    <div class="text-[10px] font-medium text-[#36414c]">
                                        {{ $reservation->guest?->full_name ?? 'No Guest' }}
                                    </div>

                                    <div class="text-[8px] text-[#929ba4] mt-1">
                                        {{ $reservation->guest?->phone ?: '-' }}
                                    </div>

                                </td>


                                <td class="px-4 py-4">

                                    @if($physicalRoom)

                                        <div class="text-[13px] font-medium text-[#1677ff]">
                                            {{ $physicalRoom->room_number }}
                                        </div>

                                        <div class="text-[8px] text-[#929ba4] mt-1">
                                            {{ $roomLine?->roomType?->name ?? '-' }}
                                        </div>

                                    @else

                                        <span class="text-[9px] text-[#929ba4]">
                                            -
                                        </span>

                                    @endif

                                </td>


                                <td class="px-4 py-4">

                                    @if($departureBalance > 0)

                                        <div class="text-[10px] font-medium text-[#b64646]">
                                            {{ number_format($departureBalance, 0, ',', '.') }} ₫
                                        </div>

                                        <div class="text-[8px] text-[#929ba4] mt-1">
                                            Còn phải thu
                                        </div>

                                    @else

                                        <span class="inline-flex px-2 py-1 bg-[#edf9f1] text-[#31845b] rounded-[3px] text-[8px]">
                                            Paid
                                        </span>

                                    @endif

                                </td>


                                <td class="px-4 py-4">

                                    @if($reservation->status === 'checked_out')

                                        <span class="inline-flex px-2 py-1 bg-[#f1f3f5] text-[#68737e] rounded-[3px] text-[8px]">
                                            Checked Out
                                        </span>

                                        <div class="text-[8px] text-[#929ba4] mt-1">
                                            Đã trả phòng
                                        </div>

                                    @else

                                        <span class="inline-flex px-2 py-1 bg-[#edf9f1] text-[#31845b] rounded-[3px] text-[8px]">
                                            In House
                                        </span>

                                        <div class="text-[8px] text-[#929ba4] mt-1">
                                            Đang lưu trú
                                        </div>

                                    @endif

                                </td>


                                <td class="px-5 py-4 text-right">

                                    @if($reservation->status === 'checked_in')

                                        @if($departureBalance > 0)

                                            <a
                                                href="{{ route('pms.reservations.show', $reservation) }}"
                                                class="h-[30px] px-3 inline-flex items-center justify-center bg-[#fff7e8] border border-[#f0dfba] text-[#9a6c28] rounded-[3px] text-[9px]"
                                            >
                                                Complete Payment
                                            </a>

                                        @else

                                            <form
                                                method="POST"
                                                action="{{ route('pms.front-desk.check-out', $reservation) }}"
                                                class="inline-block"
                                                onsubmit="return confirm('Check out reservation {{ $reservation->code }}?')"
                                            >
                                                @csrf

                                                <button
                                                    type="submit"
                                                    class="h-[30px] px-4 bg-[#1677ff] text-white rounded-[3px] text-[9px]"
                                                >
                                                    Check Out
                                                </button>

                                            </form>

                                        @endif

                                    @else

                                        <a
                                            href="{{ route('pms.reservations.show', $reservation) }}"
                                            class="h-[30px] px-3 inline-flex items-center justify-center border border-[#dce1e6] bg-white text-[#59636e] rounded-[3px] text-[9px]"
                                        >
                                            View Reservation
                                        </a>

                                    @endif

                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>

        @endif

    </div>

</div>

@endsection 