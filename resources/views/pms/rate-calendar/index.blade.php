@extends('layouts.pms')

@section('title', 'Rate Calendar - CityHouse PMS')

@section('content')

<div class="w-full">

    {{-- ====================================================== --}}
    {{-- PAGE HEADER --}}
    {{-- ====================================================== --}}

    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between mb-5">

        <div>

            <h1 class="text-[18px] font-medium text-[#303942]">
                Rate Calendar
            </h1>

            <p class="text-[11px] text-[#8a949e] mt-1">
                Manage daily room rates and selling restrictions
            </p>

        </div>


        {{-- DATE NAVIGATION --}}
        <div class="flex items-center gap-2 flex-wrap">

            <a
                href="{{ route('pms.rate-calendar.index', ['start' => $startDate->subDays(7)->format('Y-m-d')]) }}"
                class="h-[34px] px-3 inline-flex items-center justify-center bg-white border border-[#dce1e6] rounded-[3px] text-[11px] text-[#5c6670] hover:bg-[#f7f8fa]"
            >
                ← Previous
            </a>


            <a
                href="{{ route('pms.rate-calendar.index') }}"
                class="h-[34px] px-3 inline-flex items-center justify-center bg-white border border-[#dce1e6] rounded-[3px] text-[11px] text-[#5c6670] hover:bg-[#f7f8fa]"
            >
                Today
            </a>


            <a
                href="{{ route('pms.rate-calendar.index', ['start' => $startDate->addDays(7)->format('Y-m-d')]) }}"
                class="h-[34px] px-3 inline-flex items-center justify-center bg-white border border-[#dce1e6] rounded-[3px] text-[11px] text-[#5c6670] hover:bg-[#f7f8fa]"
            >
                Next →
            </a>

        </div>

    </div>


    {{-- ====================================================== --}}
    {{-- FLASH MESSAGES --}}
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

            <div class="text-[11px] font-medium text-[#b64646] mb-1">
                Please check the form:
            </div>

            @foreach($errors->all() as $error)

                <div class="text-[10px] text-[#b64646]">
                    {{ $error }}
                </div>

            @endforeach

        </div>

    @endif


    {{-- ====================================================== --}}
    {{-- PROPERTY / SYNC INFO --}}
    {{-- ====================================================== --}}

    <div class="bg-white border border-[#e2e6ea] rounded-[3px] mb-5">

        <div class="px-5 py-4 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">

            <div>

                <div class="text-[10px] uppercase tracking-wide text-[#98a1aa]">
                    Property
                </div>

                <div class="text-[12px] font-medium text-[#36414c] mt-1">
                    {{ $property->name }}
                </div>

                <div class="text-[10px] text-[#929ba4] mt-1">
                    {{ $startDate->format('d/m/Y') }}
                    -
                    {{ $startDate->addDays(6)->format('d/m/Y') }}
                </div>

            </div>


            <div class="flex items-center gap-2">

                @if($property->channex_property_id)

                    <span class="inline-flex items-center gap-2 px-3 h-[28px] bg-[#edf9f1] border border-[#d7eadc] rounded-[3px] text-[10px] text-[#31845b]">

                        <span class="w-[6px] h-[6px] rounded-full bg-[#3fb76f]"></span>

                        Channex Property Mapped

                    </span>

                @else

                    <span class="inline-flex items-center gap-2 px-3 h-[28px] bg-[#fff7e8] border border-[#f0dfbd] rounded-[3px] text-[10px] text-[#9a6c28]">

                        <span class="w-[6px] h-[6px] rounded-full bg-[#e2a64c]"></span>

                        Property Not Mapped

                    </span>

                @endif

            </div>

        </div>

    </div>


    {{-- ====================================================== --}}
    {{-- RATE CALENDAR FORM --}}
    {{-- ====================================================== --}}

    <form
        method="POST"
        action="{{ route('pms.rate-calendar.update') }}"
    >

        @csrf

        <input
            type="hidden"
            name="start_date"
            value="{{ $startDate->format('Y-m-d') }}"
        >


        <div class="bg-white border border-[#e2e6ea] rounded-[3px] overflow-hidden">

            {{-- TOP BAR --}}
            <div class="px-5 py-4 border-b border-[#edf0f2] flex flex-col gap-3 md:flex-row md:items-center md:justify-between">

                <div>

                    <div class="text-[12px] font-medium text-[#36414c]">
                        Daily Rates & Restrictions
                    </div>

                    <div class="text-[10px] text-[#929ba4] mt-1">
                        Rate, minimum stay and stop sell for each Rate Plan
                    </div>

                </div>


                <div class="text-[10px] text-[#8b949d]">
                    {{ $ratePlans->count() }} Rate Plans
                </div>

            </div>


            {{-- CALENDAR TABLE --}}
            <div class="overflow-x-auto">

                <table class="w-full min-w-[1450px]">

                    {{-- TABLE HEAD --}}
                    <thead class="bg-[#fafbfc] border-b border-[#e7eaed]">

                        <tr>

                            <th class="sticky left-0 z-20 bg-[#fafbfc] min-w-[220px] w-[220px] px-5 py-3 text-left border-r border-[#e7eaed]">

                                <div class="text-[10px] uppercase tracking-wide text-[#77828d] font-medium">
                                    Rate Plan
                                </div>

                            </th>


                            @foreach($dates as $date)

                                <th class="min-w-[175px] px-3 py-3 text-center border-r border-[#edf0f2] last:border-r-0">

                                    <div class="text-[10px] uppercase text-[#8b949d] font-medium">
                                        {{ $date->format('D') }}
                                    </div>

                                    <div class="text-[12px] text-[#36414c] mt-1 font-medium">
                                        {{ $date->format('d/m') }}
                                    </div>

                                    @if($date->isToday())

                                        <div class="mt-1 text-[9px] text-[#1677ff]">
                                            Today
                                        </div>

                                    @endif

                                </th>

                            @endforeach

                        </tr>

                    </thead>


                    {{-- TABLE BODY --}}
                    <tbody class="divide-y divide-[#edf0f2]">

                        @forelse($ratePlans as $ratePlan)

                            <tr class="align-top">

                                {{-- ========================================== --}}
                                {{-- RATE PLAN INFO --}}
                                {{-- ========================================== --}}

                                <td class="sticky left-0 z-10 bg-white border-r border-[#edf0f2] px-5 py-4">

                                    <div class="text-[12px] font-medium text-[#36414c]">
                                        {{ $ratePlan->roomType?->name ?? 'Unknown Room' }}
                                    </div>

                                    <div class="text-[11px] text-[#66717d] mt-1">
                                        {{ $ratePlan->name }}
                                    </div>

                                    <div class="text-[10px] text-[#9aa2aa] mt-1">
                                        {{ $ratePlan->code }}
                                    </div>


                                    {{-- CHANNEX MAPPING --}}
                                    <div class="mt-3">

                                        @if($ratePlan->channex_rate_plan_id)

                                            @if($ratePlan->channex_sell_mode === 'per_room')

                                                <span class="inline-flex items-center gap-1.5 text-[9px] text-[#31845b]">

                                                    <span class="w-[5px] h-[5px] bg-[#3fb76f] rounded-full"></span>

                                                    Channex mapped

                                                </span>

                                            @else

                                                <span class="inline-flex items-center gap-1.5 text-[9px] text-[#b17a32]">

                                                    <span class="w-[5px] h-[5px] bg-[#e1a34b] rounded-full"></span>

                                                    {{ $ratePlan->channex_sell_mode ?? 'Unknown mode' }}

                                                </span>

                                            @endif

                                        @else

                                            <span class="inline-flex items-center gap-1.5 text-[9px] text-[#929ba4]">

                                                <span class="w-[5px] h-[5px] bg-[#c5cbd1] rounded-full"></span>

                                                Not mapped

                                            </span>

                                        @endif

                                    </div>


                                    {{-- BASE RATE --}}
                                    <div class="mt-3 pt-3 border-t border-[#f0f2f4]">

                                        <div class="text-[9px] text-[#9aa2aa]">
                                            Base Rate
                                        </div>

                                        <div class="text-[11px] text-[#56616c] mt-1">
                                            {{ number_format($ratePlan->base_rate, 0, ',', '.') }} ₫
                                        </div>

                                    </div>

                                </td>


                                {{-- ========================================== --}}
                                {{-- DAILY CELLS --}}
                                {{-- ========================================== --}}

                                @foreach($dates as $date)

                                    @php
                                        $dateString = $date->format('Y-m-d');
                                        $calendarKey = $ratePlan->id . '_' . $dateString;
                                        $calendar = $calendars->get($calendarKey);
                                    @endphp


                                    <td class="px-3 py-3 border-r border-[#edf0f2] last:border-r-0">

                                        <div class="space-y-3">

                                            {{-- RATE --}}
                                            <div>

                                                <label class="block text-[9px] uppercase tracking-wide text-[#929ba4] mb-1">
                                                    Rate
                                                </label>

                                                <div class="relative">

                                                    <input
                                                        type="number"
                                                        name="rates[{{ $ratePlan->id }}][{{ $dateString }}][rate]"
                                                        value="{{ old('rates.' . $ratePlan->id . '.' . $dateString . '.rate', $calendar?->rate ?? $ratePlan->base_rate) }}"
                                                        min="1"
                                                        step="1"
                                                        class="w-full h-[32px] px-2 pr-7 bg-white border border-[#dce1e6] rounded-[3px] text-[11px] text-right outline-none focus:border-[#1677ff]"
                                                    >

                                                    <span class="absolute right-2 top-1/2 -translate-y-1/2 text-[9px] text-[#a0a8b0]">
                                                        ₫
                                                    </span>

                                                </div>

                                            </div>


                                            {{-- MIN STAY --}}
                                            <div>

                                                <label class="block text-[9px] uppercase tracking-wide text-[#929ba4] mb-1">
                                                    Min Stay
                                                </label>

                                                <div class="flex items-center">

                                                    <input
                                                        type="number"
                                                        name="rates[{{ $ratePlan->id }}][{{ $dateString }}][min_stay]"
                                                        value="{{ old('rates.' . $ratePlan->id . '.' . $dateString . '.min_stay', $calendar?->min_stay ?? $ratePlan->min_stay) }}"
                                                        min="1"
                                                        max="365"
                                                        class="w-full h-[32px] px-2 bg-white border border-[#dce1e6] rounded-[3px] text-[11px] text-center outline-none focus:border-[#1677ff]"
                                                    >

                                                </div>

                                            </div>


                                            {{-- STOP SELL --}}
                                            <div>

                                                <label class="flex items-center justify-between gap-2 cursor-pointer">

                                                    <span class="text-[9px] uppercase tracking-wide text-[#929ba4]">
                                                        Stop Sell
                                                    </span>


                                                    <input
                                                        type="hidden"
                                                        name="rates[{{ $ratePlan->id }}][{{ $dateString }}][stop_sell]"
                                                        value="0"
                                                    >


                                                    <input
                                                        type="checkbox"
                                                        name="rates[{{ $ratePlan->id }}][{{ $dateString }}][stop_sell]"
                                                        value="1"
                                                        class="w-[14px] h-[14px]"
                                                        @checked((bool) old('rates.' . $ratePlan->id . '.' . $dateString . '.stop_sell', $calendar?->stop_sell ?? false))
                                                    >

                                                </label>

                                            </div>


                                            {{-- SYNC STATUS --}}
                                            <div class="pt-2 border-t border-[#f0f2f4]">

                                                @if(!$calendar)

                                                    <span class="text-[9px] text-[#929ba4]">
                                                        No data
                                                    </span>

                                                @elseif($calendar->sync_status === 'synced')

                                                    <span class="inline-flex items-center gap-1.5 text-[9px] text-[#31845b]">

                                                        <span class="w-[5px] h-[5px] rounded-full bg-[#3fb76f]"></span>

                                                        Synced

                                                    </span>

                                                @elseif($calendar->sync_status === 'failed')

                                                    <span class="inline-flex items-center gap-1.5 text-[9px] text-[#b64646]">

                                                        <span class="w-[5px] h-[5px] rounded-full bg-[#d95c5c]"></span>

                                                        Failed

                                                    </span>

                                                @elseif($calendar->sync_status === 'warning')

                                                    <span class="inline-flex items-center gap-1.5 text-[9px] text-[#b17a32]">

                                                        <span class="w-[5px] h-[5px] rounded-full bg-[#e1a34b]"></span>

                                                        Warning

                                                    </span>

                                                @else

                                                    <span class="inline-flex items-center gap-1.5 text-[9px] text-[#7d8791]">

                                                        <span class="w-[5px] h-[5px] rounded-full bg-[#c5cbd1]"></span>

                                                        Pending

                                                    </span>

                                                @endif

                                            </div>

                                        </div>

                                    </td>

                                @endforeach

                            </tr>


                        @empty

                            <tr>

                                <td
                                    colspan="{{ $dates->count() + 1 }}"
                                    class="py-16 text-center"
                                >

                                    <div class="text-[12px] text-[#7b858f]">
                                        No active Rate Plans found.
                                    </div>

                                    <div class="text-[10px] text-[#a0a8b0] mt-1">
                                        Create a Rate Plan before using Rate Calendar.
                                    </div>

                                    <a
                                        href="{{ route('pms.rate-plans.create') }}"
                                        class="inline-flex mt-4 h-[32px] px-4 items-center bg-[#1677ff] text-white rounded-[3px] text-[10px]"
                                    >
                                        Create Rate Plan
                                    </a>

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>


            {{-- ====================================================== --}}
            {{-- FOOTER --}}
            {{-- ====================================================== --}}

            @if($ratePlans->count())

                <div class="px-5 py-4 border-t border-[#edf0f2] bg-[#fafbfc] flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">

                    <div>

                        <div class="text-[10px] text-[#68737e]">
                            Changes are saved to PMS first, then synced to mapped Channex Rate Plans.
                        </div>

                        <div class="text-[9px] text-[#9aa2aa] mt-1">
                            Phase 1 currently syncs Rate Plans using per_room sell mode.
                        </div>

                    </div>


                    <button
                        type="submit"
                        class="h-[36px] px-5 inline-flex items-center justify-center bg-[#1677ff] text-white rounded-[3px] text-[11px] font-medium hover:bg-[#0969da]"
                    >
                        Save & Sync
                    </button>

                </div>

            @endif

        </div>

    </form>

</div>

@endsection