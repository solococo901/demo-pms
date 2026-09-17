@extends('layouts.pms')

@section('title', 'Edit Reservation - CityHouse PMS')

@section('content')

<div class="max-w-[1100px]">

    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between mb-5">

        <div>

            <div class="flex items-center gap-3">

                <h1 class="text-[18px] font-medium text-[#303942]">
                    {{ $reservation->code }}
                </h1>


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

                @else

                    <span class="px-2 py-1 bg-[#fff7e8] text-[#9a6c28] rounded-[3px] text-[9px]">
                        {{ ucwords(str_replace('_', ' ', $reservation->status)) }}
                    </span>

                @endif

            </div>


            <div class="text-[10px] text-[#929ba4] mt-1">

                {{ $reservation->guest?->full_name ?? 'No guest' }}

                ·

                {{ number_format($reservation->total_amount, 0, ',', '.') }} ₫

            </div>

        </div>


        <a
            href="{{ route('pms.reservations.index') }}"
            class="h-[34px] px-4 inline-flex items-center border border-[#dce1e6] bg-white rounded-[3px] text-[11px]"
        >
            ← Back
        </a>

    </div>


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


    <form
        method="POST"
        action="{{ route('pms.reservations.update', $reservation) }}"
    >

        @csrf
        @method('PUT')


        <div class="bg-white border border-[#e2e6ea] rounded-[3px]">

            <div class="px-5 py-4 border-b border-[#edf0f2]">

                <div class="text-[12px] font-medium text-[#36414c]">
                    Reservation Information
                </div>

                <div class="text-[10px] text-[#929ba4] mt-1">
                    Update guest, stay and pricing information
                </div>

            </div>


            <div class="p-5">

                @include('pms.reservations._form')

            </div>


            <div class="px-5 py-4 border-t border-[#edf0f2] bg-[#fafbfc] flex items-center justify-between">

                <div class="text-[9px] text-[#929ba4]">
                    Payment:
                    {{ ucfirst($reservation->payment_status) }}
                </div>


                <div class="flex gap-2">

                    <a
                        href="{{ route('pms.reservations.index') }}"
                        class="h-[34px] px-4 inline-flex items-center border border-[#dce1e6] bg-white rounded-[3px] text-[11px]"
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

        </div>

    </form>

</div>

@endsection