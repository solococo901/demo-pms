@extends('layouts.pms')

@section('title', 'Reservations - CityHouse PMS')

@section('content')

<div class="w-full">

    {{-- HEADER --}}
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between mb-5">

        <div>

            <h1 class="text-[18px] font-medium text-[#303942]">
                Reservations
            </h1>

            <p class="text-[11px] text-[#8a949e] mt-1">
                Manage hotel bookings and stay information
            </p>

        </div>


        <a
            href="{{ route('pms.reservations.create') }}"
            class="h-[34px] px-4 inline-flex items-center justify-center bg-[#1677ff] text-white rounded-[3px] text-[11px] font-medium hover:bg-[#0969da]"
        >
            + New Reservation
        </a>

    </div>


    {{-- FLASH --}}
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


    {{-- SUMMARY --}}
    <div class="bg-white border border-[#e2e6ea] rounded-[3px] mb-4">

        <div class="px-5 py-4 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">

            <div>

                <div class="text-[9px] uppercase tracking-wide text-[#98a1aa]">
                    Property
                </div>

                <div class="text-[12px] font-medium text-[#36414c] mt-1">
                    {{ $property->name }}
                </div>

            </div>


            <div class="flex items-center gap-6">

                <div>

                    <div class="text-[9px] uppercase tracking-wide text-[#98a1aa]">
                        Total
                    </div>

                    <div class="text-[16px] font-medium text-[#36414c] mt-1">
                        {{ $reservations->count() }}
                    </div>

                </div>


                <div>

                    <div class="text-[9px] uppercase tracking-wide text-[#98a1aa]">
                        Confirmed
                    </div>

                    <div class="text-[16px] font-medium text-[#1677ff] mt-1">
                        {{ $reservations->where('status', 'confirmed')->count() }}
                    </div>

                </div>


                <div>

                    <div class="text-[9px] uppercase tracking-wide text-[#98a1aa]">
                        Checked In
                    </div>

                    <div class="text-[16px] font-medium text-[#31845b] mt-1">
                        {{ $reservations->where('status', 'checked_in')->count() }}
                    </div>

                </div>


                <div>

                    <div class="text-[9px] uppercase tracking-wide text-[#98a1aa]">
                        Unpaid
                    </div>

                    <div class="text-[16px] font-medium text-[#b17a32] mt-1">
                        {{ $reservations->where('payment_status', 'unpaid')->count() }}
                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- FILTER --}}
    <form
        method="GET"
        action="{{ route('pms.reservations.index') }}"
        class="bg-white border border-[#e2e6ea] rounded-[3px] mb-4"
    >

        <div class="p-4">

            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-[1.5fr_1fr_1fr_1fr_auto] gap-3">

                <div>

                    <label class="block text-[9px] uppercase tracking-wide text-[#8c969f] mb-1.5">
                        Search
                    </label>

                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Reservation, guest, phone..."
                        class="w-full h-[34px] px-3 border border-[#dce1e6] rounded-[3px] text-[11px]"
                    >

                </div>


                <div>

                    <label class="block text-[9px] uppercase tracking-wide text-[#8c969f] mb-1.5">
                        Status
                    </label>

                    <select
                        name="status"
                        class="w-full h-[34px] px-3 border border-[#dce1e6] bg-white rounded-[3px] text-[11px]"
                    >

                        <option value="">All Status</option>
                        <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                        <option value="confirmed" @selected(request('status') === 'confirmed')>Confirmed</option>
                        <option value="checked_in" @selected(request('status') === 'checked_in')>Checked In</option>
                        <option value="checked_out" @selected(request('status') === 'checked_out')>Checked Out</option>
                        <option value="cancelled" @selected(request('status') === 'cancelled')>Cancelled</option>
                        <option value="no_show" @selected(request('status') === 'no_show')>No Show</option>

                    </select>

                </div>


                <div>

                    <label class="block text-[9px] uppercase tracking-wide text-[#8c969f] mb-1.5">
                        Payment
                    </label>

                    <select
                        name="payment_status"
                        class="w-full h-[34px] px-3 border border-[#dce1e6] bg-white rounded-[3px] text-[11px]"
                    >

                        <option value="">All Payment</option>
                        <option value="unpaid" @selected(request('payment_status') === 'unpaid')>Unpaid</option>
                        <option value="partial" @selected(request('payment_status') === 'partial')>Partial</option>
                        <option value="paid" @selected(request('payment_status') === 'paid')>Paid</option>
                        <option value="refunded" @selected(request('payment_status') === 'refunded')>Refunded</option>

                    </select>

                </div>


                <div>

                    <label class="block text-[9px] uppercase tracking-wide text-[#8c969f] mb-1.5">
                        Source
                    </label>

                    <select
                        name="source"
                        class="w-full h-[34px] px-3 border border-[#dce1e6] bg-white rounded-[3px] text-[11px]"
                    >

                        <option value="">All Sources</option>
                        <option value="direct" @selected(request('source') === 'direct')>Direct</option>
                        <option value="website" @selected(request('source') === 'website')>Website</option>
                        <option value="walk_in" @selected(request('source') === 'walk_in')>Walk In</option>
                        <option value="phone" @selected(request('source') === 'phone')>Phone</option>
                        <option value="channex" @selected(request('source') === 'channex')>Channex</option>
                        <option value="ota" @selected(request('source') === 'ota')>OTA</option>

                    </select>

                </div>


                <div class="flex items-end gap-2">

                    <button
                        type="submit"
                        class="h-[34px] px-4 bg-[#1677ff] text-white rounded-[3px] text-[10px]"
                    >
                        Filter
                    </button>

                    <a
                        href="{{ route('pms.reservations.index') }}"
                        class="h-[34px] px-3 inline-flex items-center border border-[#dce1e6] rounded-[3px] text-[10px]"
                    >
                        Reset
                    </a>

                </div>

            </div>

        </div>

    </form>


    {{-- TABLE --}}
    <div class="bg-white border border-[#e2e6ea] rounded-[3px] overflow-hidden">

        <div class="overflow-x-auto">

            <table class="w-full min-w-[1200px]">

                <thead class="bg-[#fafbfc] border-b border-[#e7eaed]">

                    <tr class="text-left text-[9px] uppercase tracking-wide text-[#77828d]">

                        <th class="px-5 py-3 font-medium">Reservation</th>
                        <th class="px-5 py-3 font-medium">Guest</th>
                        <th class="px-5 py-3 font-medium">Stay</th>
                        <th class="px-5 py-3 font-medium">Room</th>
                        <th class="px-5 py-3 font-medium">Source</th>
                        <th class="px-5 py-3 font-medium">Total</th>
                        <th class="px-5 py-3 font-medium">Payment</th>
                        <th class="px-5 py-3 font-medium">Status</th>
                        <th class="px-5 py-3 font-medium text-right">Actions</th>

                    </tr>

                </thead>


                <tbody class="divide-y divide-[#edf0f2]">

                    @forelse($reservations as $reservation)

                        @php
                            $roomLine = $reservation->rooms->first();
                        @endphp

                        <tr class="hover:bg-[#fafcff]">

                            <td class="px-5 py-4">

                                <a
                                    href="{{ route('pms.reservations.show', $reservation) }}"
                                    class="text-[11px] font-medium text-[#1677ff]"
                                >
                                    {{ $reservation->code }}
                                </a>

                                @if($reservation->external_reservation_id)

                                    <div class="text-[9px] text-[#9ba4ad] mt-1">
                                        Ext: {{ $reservation->external_reservation_id }}
                                    </div>

                                @endif

                            </td>


                            <td class="px-5 py-4">

                                <div class="text-[11px] font-medium text-[#36414c]">
                                    {{ $reservation->guest?->full_name ?? 'No guest' }}
                                </div>

                                <div class="text-[9px] text-[#9ba4ad] mt-1">
                                    {{ $reservation->guest?->phone ?? '-' }}
                                </div>

                            </td>


                            <td class="px-5 py-4">

                                @if($roomLine)

                                    <div class="text-[10px] text-[#56616c]">
                                        {{ \Carbon\CarbonImmutable::parse($roomLine->check_in)->format('d/m/Y') }}
                                    </div>

                                    <div class="text-[9px] text-[#9ba4ad] mt-1">
                                        →
                                        {{ \Carbon\CarbonImmutable::parse($roomLine->check_out)->format('d/m/Y') }}
                                    </div>

                                @else

                                    <span class="text-[10px] text-[#9ba4ad]">
                                        -
                                    </span>

                                @endif

                            </td>


                            <td class="px-5 py-4">

                                <div class="text-[10px] text-[#56616c]">
                                    {{ $roomLine?->roomType?->name ?? '-' }}
                                </div>

                                <div class="text-[9px] text-[#9ba4ad] mt-1">

                                    @if($roomLine?->room)

                                        Room {{ $roomLine->room->room_number }}

                                    @else

                                        Not assigned

                                    @endif

                                </div>

                            </td>


                            <td class="px-5 py-4">

                                <div class="text-[10px] text-[#56616c]">
                                    {{ ucwords(str_replace('_', ' ', $reservation->source)) }}
                                </div>

                                @if($reservation->channel)

                                    <div class="text-[9px] text-[#9ba4ad] mt-1">
                                        {{ $reservation->channel }}
                                    </div>

                                @endif

                            </td>


                            <td class="px-5 py-4 text-[11px] font-medium text-[#36414c]">

                                {{ number_format($reservation->total_amount, 0, ',', '.') }} ₫

                            </td>


                            <td class="px-5 py-4">

                                @if($reservation->payment_status === 'paid')

                                    <span class="text-[9px] text-[#31845b]">
                                        ● Paid
                                    </span>

                                @elseif($reservation->payment_status === 'partial')

                                    <span class="text-[9px] text-[#b17a32]">
                                        ● Partial
                                    </span>

                                @elseif($reservation->payment_status === 'refunded')

                                    <span class="text-[9px] text-[#747d86]">
                                        ● Refunded
                                    </span>

                                @else

                                    <span class="text-[9px] text-[#b64646]">
                                        ● Unpaid
                                    </span>

                                @endif

                            </td>


                            <td class="px-5 py-4">

                                @if($reservation->status === 'confirmed')

                                    <span class="px-2 py-1 bg-[#eef5ff] text-[#1677ff] rounded-[3px] text-[9px]">
                                        Confirmed
                                    </span>

                                @elseif($reservation->status === 'checked_in')

                                    <span class="px-2 py-1 bg-[#edf9f1] text-[#31845b] rounded-[3px] text-[9px]">
                                        Checked In
                                    </span>

                                @elseif($reservation->status === 'checked_out')

                                    <span class="px-2 py-1 bg-[#f3f4f5] text-[#68737e] rounded-[3px] text-[9px]">
                                        Checked Out
                                    </span>

                                @elseif($reservation->status === 'cancelled')

                                    <span class="px-2 py-1 bg-[#fff0f0] text-[#b64646] rounded-[3px] text-[9px]">
                                        Cancelled
                                    </span>

                                @elseif($reservation->status === 'no_show')

                                    <span class="px-2 py-1 bg-[#fff7e8] text-[#9a6c28] rounded-[3px] text-[9px]">
                                        No Show
                                    </span>

                                @else

                                    <span class="px-2 py-1 bg-[#fff7e8] text-[#9a6c28] rounded-[3px] text-[9px]">
                                        Pending
                                    </span>

                                @endif

                            </td>


                            <td class="px-5 py-4">

                                <div class="flex justify-end gap-3">

                                    <a
                                        href="{{ route('pms.reservations.show', $reservation) }}"
                                        class="text-[10px] text-[#1677ff]"
                                    >
                                        Edit
                                    </a>


                                    @if(!in_array($reservation->status, ['cancelled', 'checked_out'], true))

                                        <form
                                            method="POST"
                                            action="{{ route('pms.reservations.destroy', $reservation) }}"
                                            onsubmit="return confirm('Cancel reservation {{ $reservation->code }}?')"
                                        >

                                            @csrf
                                            @method('DELETE')

                                            <button
                                                type="submit"
                                                class="text-[10px] text-[#c04b4b]"
                                            >
                                                Cancel
                                            </button>

                                        </form>

                                    @endif

                                </div>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="9" class="py-16 text-center">

                                <div class="text-[12px] text-[#76818c]">
                                    No reservations found
                                </div>

                                <a
                                    href="{{ route('pms.reservations.create') }}"
                                    class="inline-flex mt-4 h-[32px] px-4 items-center bg-[#1677ff] text-white rounded-[3px] text-[10px]"
                                >
                                    + New Reservation
                                </a>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

</div>

@endsection