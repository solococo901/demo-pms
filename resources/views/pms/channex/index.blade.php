@extends('layouts.pms')

@section('title', 'Channex - CityHouse PMS')

@section('content')

    <div>

        {{-- HEADER --}}
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-5">

            <div>

                <h1 class="text-[18px] font-medium text-[#303641]">
                    Channex
                </h1>

                <p class="text-[11px] text-[#8a949e] mt-1">
                    Channel Manager Integration
                </p>

            </div>


            <a href="{{ route('pms.channex.index') }}"
                class="h-[34px] px-4 inline-flex items-center bg-white border border-[#dce1e6] rounded-[3px] text-[11px] text-[#5e6975]">
                ↻ Refresh
            </a>

        </div>


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


        @if($errors->any())

            <div class="mb-4 px-4 py-3 bg-[#fff5f5] border border-[#f0d1d1] text-[#b34d4d] text-[11px] rounded-[3px]">

                @foreach($errors->all() as $error)

                    <div>
                        {{ $error }}
                    </div>

                @endforeach

            </div>

        @endif


        {{-- CONNECTION --}}
        <div class="bg-white border border-[#e2e6ea] rounded-[3px] mb-5">

            <div class="px-5 py-4 border-b border-[#edf0f2] flex items-center justify-between">

                <div>

                    <div class="text-[12px] font-medium">
                        API Connection
                    </div>

                    <div class="text-[10px] text-[#9aa2aa] mt-1">
                        Connection between CityHouse PMS and Channex
                    </div>

                </div>


                @if($connected)

                    <span class="inline-flex items-center gap-2 text-[10px] text-[#31845b]">

                        <span class="w-[7px] h-[7px] rounded-full bg-[#3fb76f]"></span>

                        Connected

                    </span>

                @else

                    <span class="inline-flex items-center gap-2 text-[10px] text-[#b94c4c]">

                        <span class="w-[7px] h-[7px] rounded-full bg-[#dc5b5b]"></span>

                        Connection Failed

                    </span>

                @endif

            </div>


            <div class="grid md:grid-cols-3 divide-x divide-[#edf0f2]">

                <div class="px-5 py-4">

                    <div class="text-[10px] uppercase text-[#9099a2]">
                        Provider
                    </div>

                    <div class="text-[12px] font-medium mt-2">
                        Channex
                    </div>

                </div>


                <div class="px-5 py-4">

                    <div class="text-[10px] uppercase text-[#9099a2]">
                        PMS Property
                    </div>

                    <div class="text-[12px] font-medium mt-2">
                        {{ $property->name }}
                    </div>

                </div>


                <div class="px-5 py-4">

                    <div class="text-[10px] uppercase text-[#9099a2]">
                        Mapping
                    </div>

                    <div class="mt-2">

                        @if($property->channex_property_id)

                            <span class="text-[11px] text-[#31845b]">
                                Mapped
                            </span>

                        @else

                            <span class="text-[11px] text-[#929ba4]">
                                Not mapped
                            </span>

                        @endif

                    </div>

                </div>

            </div>


            @if($error)

                <div class="px-5 py-4 border-t border-[#edf0f2] bg-[#fff6f6]">

                    <div class="text-[11px] text-[#b94c4c]">
                        {{ $error }}
                    </div>

                </div>

            @endif

        </div>


        {{-- ====================================================== --}}
        {{-- CHANNEX PROPERTIES --}}
        {{-- ====================================================== --}}

        <div class="bg-white border border-[#e2e6ea] rounded-[3px] mb-5">

            <div class="px-5 py-4 border-b border-[#edf0f2]">

                <div class="text-[12px] font-medium">
                    Channex Properties
                </div>

                <div class="text-[10px] text-[#9aa2aa] mt-1">
                    Properties available through your API Key
                </div>

            </div>


            <div class="overflow-x-auto">

                <table class="w-full">

                    <thead class="bg-[#fafbfc] border-b border-[#e7eaed]">

                        <tr class="text-left text-[10px] uppercase tracking-wide text-[#77828d]">

                            <th class="px-5 py-3 font-medium">
                                Property
                            </th>

                            <th class="px-5 py-3 font-medium">
                                Currency
                            </th>

                            <th class="px-5 py-3 font-medium">
                                Channex ID
                            </th>

                            <th class="px-5 py-3 font-medium">
                                Status
                            </th>

                            <th class="px-5 py-3 font-medium text-right">
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody class="divide-y divide-[#edf0f2]">

                        @forelse($channexProperties as $item)

                            @php

                                $attributes =
                                    $item['attributes'] ?? [];

                                $channexId =
                                    $item['id']
                                    ?? $attributes['id']
                                    ?? null;

                                $mapped =
                                    $property->channex_property_id
                                    === $channexId;

                            @endphp


                            <tr class="hover:bg-[#fafcff]">

                                <td class="px-5 py-4">

                                    <div class="text-[12px] font-medium text-[#36414c]">
                                        {{ $attributes['title'] ?? '-' }}
                                    </div>

                                </td>


                                <td class="px-5 py-4 text-[11px] text-[#66717d]">

                                    {{ $attributes['currency'] ?? '-' }}

                                </td>


                                <td class="px-5 py-4">

                                    <code class="text-[10px] text-[#66717d]">
                                                {{ $channexId }}
                                            </code>

                                </td>


                                <td class="px-5 py-4">

                                    @if($mapped)

                                        <span class="inline-flex items-center gap-2 text-[10px] text-[#31845b]">

                                            <span class="w-[6px] h-[6px] bg-[#3fb76f] rounded-full"></span>

                                            Mapped

                                        </span>

                                    @else

                                        <span class="text-[10px] text-[#98a1aa]">
                                            Available
                                        </span>

                                    @endif

                                </td>


                                <td class="px-5 py-4 text-right">

                                    @if($mapped)

                                        <form method="POST" action="{{ route('pms.channex.disconnect') }}">

                                            @csrf
                                            @method('DELETE')

                                            <button class="text-[11px] text-[#dc5b5b] hover:underline">
                                                Remove Mapping
                                            </button>

                                        </form>

                                    @else

                                        <form method="POST" action="{{ route('pms.channex.map-property') }}">

                                            @csrf

                                            <input type="hidden" name="channex_property_id" value="{{ $channexId }}">

                                            <button type="submit"
                                                class="h-[30px] px-3 bg-[#1677ff] text-white rounded-[3px] text-[10px]">
                                                Map Property
                                            </button>

                                        </form>

                                    @endif

                                </td>

                            </tr>


                        @empty

                            <tr>

                                <td colspan="5" class="py-14 text-center text-[11px] text-[#929ba4]">
                                    No Channex properties found.
                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>


        @if($property->channex_property_id)

            {{-- ====================================================== --}}
            {{-- ROOM TYPE MAPPING --}}
            {{-- ====================================================== --}}

            <div class="bg-white border border-[#e2e6ea] rounded-[3px] mb-5">

                {{-- HEADER --}}
                <div class="px-5 py-4 border-b border-[#edf0f2] flex items-center justify-between">

                    <div>

                        <div class="text-[12px] font-medium">
                            Room Type Mapping
                        </div>

                        <div class="text-[10px] text-[#9aa2aa] mt-1">
                            Map PMS Room Types with Channex Room Types
                        </div>

                    </div>


                    @php

                        $mappedCount =
                            $roomTypes
                                ->whereNotNull(
                                    'channex_room_type_id'
                                )
                                ->count();

                    @endphp


                    <div class="text-[10px] text-[#7d8791]">

                        {{ $mappedCount }}
                        /
                        {{ $roomTypes->count() }}
                        mapped

                    </div>

                </div>


                <form method="POST" action="{{ route(
                'pms.channex.map-room-types'
            ) }}">

                    @csrf


                    <div class="overflow-x-auto">

                        <table class="w-full">

                            {{-- TABLE HEADER --}}
                            <thead class="bg-[#fafbfc] border-b border-[#e7eaed]">

                                <tr class="text-left text-[10px] uppercase tracking-wide text-[#77828d]">

                                    <th class="px-5 py-3 font-medium">
                                        PMS Room Type
                                    </th>

                                    <th class="px-5 py-3 font-medium">
                                        PMS Code
                                    </th>

                                    <th class="px-5 py-3 font-medium">
                                        Rooms
                                    </th>

                                    <th class="px-5 py-3 font-medium w-[70px] text-center">
                                        Mapping
                                    </th>

                                    <th class="px-5 py-3 font-medium">
                                        Channex Room Type
                                    </th>

                                    <th class="px-5 py-3 font-medium">
                                        Status
                                    </th>

                                </tr>

                            </thead>


                            <tbody class="divide-y divide-[#edf0f2]">

                                @forelse($roomTypes as $roomType)

                                    <tr class="hover:bg-[#fafcff]">

                                        {{-- PMS ROOM TYPE --}}
                                        <td class="px-5 py-4">

                                            <div class="text-[12px] font-medium text-[#36414c]">
                                                {{ $roomType->name }}
                                            </div>

                                        </td>


                                        {{-- CODE --}}
                                        <td class="px-5 py-4">

                                            <code class="text-[10px] text-[#66717d]">
                                            {{ $roomType->code }}
                                        </code>

                                        </td>


                                        {{-- ROOMS --}}
                                        <td class="px-5 py-4 text-[11px] text-[#66717d]">

                                            {{ $roomType->total_rooms }}

                                        </td>


                                        {{-- ARROW --}}
                                        <td class="px-5 py-4 text-center">

                                            <span class="text-[#a0a8b0]">
                                                →
                                            </span>

                                        </td>


                                        {{-- CHANNEX SELECT --}}
                                        <td class="px-5 py-4">

                                            <select name="mappings[{{ $roomType->id }}]"
                                                class="w-full max-w-[360px] h-[34px] px-3 bg-white border border-[#dce1e6] rounded-[3px] text-[11px] outline-none focus:border-[#1677ff]">

                                                <option value="">
                                                    -- Not mapped --
                                                </option>


                                                @foreach($channexRoomTypes as $channexRoomType)

                                                    @php

                                                        $attr =
                                                            $channexRoomType[
                                                                'attributes'
                                                            ] ?? [];

                                                        $channexId =
                                                            $channexRoomType['id']
                                                            ??
                                                            $attr['id']
                                                            ??
                                                            null;

                                                    @endphp


                                                    <option value="{{ $channexId }}"
                                                        @selected($roomType->channex_room_type_id === $channexId)>

                                                        {{ $attr['title'] ?? 'Untitled' }}

                                                        @if(isset($attr['count_of_rooms']))

                                                            —
                                                            {{ $attr['count_of_rooms'] }}
                                                            rooms

                                                        @endif

                                                    </option>

                                                @endforeach

                                            </select>

                                        </td>


                                        {{-- STATUS --}}
                                        <td class="px-5 py-4">

                                            @if($roomType->channex_room_type_id)

                                                <span class="inline-flex items-center gap-2 text-[10px] text-[#31845b]">

                                                    <span class="w-[6px] h-[6px] bg-[#3fb76f] rounded-full"></span>

                                                    Mapped

                                                </span>

                                            @else

                                                <span class="inline-flex items-center gap-2 text-[10px] text-[#929ba4]">

                                                    <span class="w-[6px] h-[6px] bg-[#c5cbd1] rounded-full"></span>

                                                    Not mapped

                                                </span>

                                            @endif

                                        </td>

                                    </tr>


                                @empty

                                                    <tr>

                                                        <td colspan="6" class="py-12 text-center">

                                                            <div class="text-[11px] text-[#929ba4]">
                                                                No PMS Room Types found.
                                                            </div>

                                                            <a href="{{ route(
                                        'pms.room-types.create'
                                    ) }}" class="inline-flex mt-3 text-[11px] text-[#1677ff]">
                                                                Create Room Type
                                                            </a>

                                                        </td>

                                                    </tr>

                                @endforelse

                            </tbody>

                        </table>

                    </div>


                    {{-- FOOTER --}}
                    @if($roomTypes->count())

                        <div class="px-5 py-4 border-t border-[#edf0f2] flex items-center justify-between">

                            <div class="text-[10px] text-[#959ea7]">

                                Each Channex Room Type can only
                                be mapped to one PMS Room Type.

                            </div>


                            <button type="submit"
                                class="h-[34px] px-4 bg-[#1677ff] text-white rounded-[3px] text-[11px] font-medium hover:bg-[#0969da]">
                                Save Mapping
                            </button>

                        </div>

                    @endif

                </form>

            </div>


            {{-- ====================================================== --}}
            {{-- RATE PLAN MAPPING --}}
            {{-- ====================================================== --}}

            <div class="bg-white border border-[#e2e6ea] rounded-[3px] mb-5">

                {{-- HEADER --}}
                <div class="px-5 py-4 border-b border-[#edf0f2] flex items-center justify-between">

                    <div>

                        <div class="text-[12px] font-medium">
                            Rate Plan Mapping
                        </div>

                        <div class="text-[10px] text-[#9aa2aa] mt-1">
                            Map PMS Rate Plans with Channex Rate Plans
                        </div>

                    </div>


                    @php
                        $mappedRatePlanCount = $ratePlans
                            ->whereNotNull('channex_rate_plan_id')
                            ->count();
                    @endphp


                    <div class="text-[10px] text-[#7d8791]">

                        {{ $mappedRatePlanCount }}
                        /
                        {{ $ratePlans->count() }}
                        mapped

                    </div>

                </div>


                <form method="POST" action="{{ route('pms.channex.map-rate-plans') }}">

                    @csrf


                    <div class="overflow-x-auto">

                        <table class="w-full">

                            {{-- HEADER --}}
                            <thead class="bg-[#fafbfc] border-b border-[#e7eaed]">

                                <tr class="text-left text-[10px] uppercase tracking-wide text-[#77828d]">

                                    <th class="px-5 py-3 font-medium">
                                        PMS Rate Plan
                                    </th>

                                    <th class="px-5 py-3 font-medium">
                                        Room Type
                                    </th>

                                    <th class="px-5 py-3 font-medium">
                                        Base Rate
                                    </th>

                                    <th class="px-5 py-3 font-medium w-[70px] text-center">
                                        Mapping
                                    </th>

                                    <th class="px-5 py-3 font-medium">
                                        Channex Rate Plan
                                    </th>

                                    <th class="px-5 py-3 font-medium">
                                        Status
                                    </th>

                                </tr>

                            </thead>


                            <tbody class="divide-y divide-[#edf0f2]">

                                @forelse($ratePlans as $ratePlan)

                                                    <tr class="hover:bg-[#fafcff]">

                                                        {{-- PMS RATE PLAN --}}
                                                        <td class="px-5 py-4">

                                                            <div class="text-[12px] font-medium text-[#36414c]">
                                                                {{ $ratePlan->name }}
                                                            </div>

                                                            <div class="text-[10px] text-[#929ba4] mt-1">
                                                                {{ $ratePlan->code }}
                                                            </div>

                                                        </td>


                                                        {{-- ROOM TYPE --}}
                                                        <td class="px-5 py-4">

                                                            <div class="text-[11px] text-[#66717d]">
                                                                {{ $ratePlan->roomType?->name ?? '-' }}
                                                            </div>

                                                            @if($ratePlan->roomType?->channex_room_type_id)

                                                                <div class="text-[9px] text-[#31845b] mt-1">
                                                                    Room mapped
                                                                </div>

                                                            @else

                                                                <div class="text-[9px] text-[#b17a32] mt-1">
                                                                    Room not mapped
                                                                </div>

                                                            @endif

                                                        </td>


                                                        {{-- BASE RATE --}}
                                                        <td class="px-5 py-4 text-[11px] text-[#66717d]">

                                                            {{ number_format(
                                        $ratePlan->base_rate,
                                        0,
                                        ',',
                                        '.'
                                    ) }} ₫

                                                        </td>


                                                        {{-- ARROW --}}
                                                        <td class="px-5 py-4 text-center">

                                                            <span class="text-[#a0a8b0]">
                                                                →
                                                            </span>

                                                        </td>


                                                        {{-- CHANNEX RATE PLAN --}}
                                                        <td class="px-5 py-4">

                                                            <select name="mappings[{{ $ratePlan->id }}]"
                                                                class="w-full max-w-[380px] h-[34px] px-3 bg-white border border-[#dce1e6] rounded-[3px] text-[11px] outline-none focus:border-[#1677ff]"
                                                                @disabled(!$ratePlan->roomType?->channex_room_type_id)>

                                                                <option value="">
                                                                    -- Not mapped --
                                                                </option>


                                                                @foreach($channexRatePlans as $remoteRatePlan)

                                                                    @php
                                                                        $remoteAttributes = $remoteRatePlan['attributes'] ?? [];

                                                                        $remoteId = $remoteRatePlan['id']
                                                                            ?? ($remoteAttributes['id'] ?? null);

                                                                        $remoteRoomTypeId = $remoteAttributes['room_type_id']
                                                                            ?? null;

                                                                        $sameRoomType =
                                                                            $ratePlan->roomType
                                                                            &&
                                                                            $ratePlan->roomType->channex_room_type_id
                                                                            &&
                                                                            $remoteRoomTypeId === $ratePlan->roomType->channex_room_type_id;
                                                                    @endphp


                                                                    @if($sameRoomType)

                                                                        <option value="{{ $remoteId }}"
                                                                            @selected($ratePlan->channex_rate_plan_id === $remoteId)>

                                                                            {{ $remoteAttributes['title'] ?? 'Untitled' }}

                                                                            @if(!empty($remoteAttributes['sell_mode']))
                                                                                — {{ $remoteAttributes['sell_mode'] }}
                                                                            @endif

                                                                        </option>

                                                                    @endif

                                                                @endforeach

                                                            </select>

                                                        </td>


                                                        {{-- STATUS --}}
                                                        <td class="px-5 py-4">

                                                            @if($ratePlan->channex_rate_plan_id)

                                                                <span class="inline-flex items-center gap-2 text-[10px] text-[#31845b]">

                                                                    <span class="w-[6px] h-[6px] bg-[#3fb76f] rounded-full"></span>

                                                                    Mapped

                                                                </span>

                                                            @elseif(!$ratePlan->roomType?->channex_room_type_id)

                                                                <span class="inline-flex items-center gap-2 text-[10px] text-[#b17a32]">

                                                                    <span class="w-[6px] h-[6px] bg-[#e1a34b] rounded-full"></span>

                                                                    Map Room first

                                                                </span>

                                                            @else

                                                                <span class="inline-flex items-center gap-2 text-[10px] text-[#929ba4]">

                                                                    <span class="w-[6px] h-[6px] bg-[#c5cbd1] rounded-full"></span>

                                                                    Not mapped

                                                                </span>

                                                            @endif

                                                        </td>

                                                    </tr>


                                @empty

                                    <tr>

                                        <td colspan="6" class="py-12 text-center">

                                            <div class="text-[11px] text-[#929ba4]">
                                                No PMS Rate Plans found.
                                            </div>

                                            <a href="{{ route('pms.rate-plans.create') }}"
                                                class="inline-flex mt-3 text-[11px] text-[#1677ff]">
                                                Create Rate Plan
                                            </a>

                                        </td>

                                    </tr>

                                @endforelse

                            </tbody>

                        </table>

                    </div>


                    {{-- FOOTER --}}
                    @if($ratePlans->count())

                        <div class="px-5 py-4 border-t border-[#edf0f2] flex items-center justify-between">

                            <div class="text-[10px] text-[#959ea7]">

                                Rate Plans can only be mapped to
                                Channex Rate Plans from the same mapped Room Type.

                            </div>


                            <button type="submit"
                                class="h-[34px] px-4 bg-[#1677ff] text-white rounded-[3px] text-[11px] font-medium hover:bg-[#0969da]">
                                Save Rate Mapping
                            </button>

                        </div>

                    @endif

                </form>

            </div>


            {{-- ====================================================== --}}
            {{-- ROOM TYPES FROM CHANNEX --}}
            {{-- ====================================================== --}}

            <div class="bg-white border border-[#e2e6ea] rounded-[3px] mb-5">

                <div class="px-5 py-4 border-b border-[#edf0f2]">

                    <div class="text-[12px] font-medium">
                        Channex Room Types
                    </div>

                    <div class="text-[10px] text-[#9aa2aa] mt-1">
                        Room Types loaded directly from Channex
                    </div>

                </div>


                <div class="overflow-x-auto">

                    <table class="w-full">

                        <thead class="bg-[#fafbfc] border-b border-[#e7eaed]">

                            <tr class="text-left text-[10px] uppercase text-[#77828d]">

                                <th class="px-5 py-3 font-medium">
                                    Room Type
                                </th>

                                <th class="px-5 py-3 font-medium">
                                    Rooms
                                </th>

                                <th class="px-5 py-3 font-medium">
                                    Adults
                                </th>

                                <th class="px-5 py-3 font-medium">
                                    Children
                                </th>

                                <th class="px-5 py-3 font-medium">
                                    Channex ID
                                </th>

                            </tr>

                        </thead>


                        <tbody class="divide-y divide-[#edf0f2]">

                            @forelse($channexRoomTypes as $item)

                                @php
                                    $attr =
                                        $item['attributes'] ?? [];
                                @endphp

                                <tr>

                                    <td class="px-5 py-4 text-[12px] font-medium">
                                        {{ $attr['title'] ?? '-' }}
                                    </td>

                                    <td class="px-5 py-4 text-[11px] text-[#66717d]">
                                        {{ $attr['count_of_rooms'] ?? '-' }}
                                    </td>

                                    <td class="px-5 py-4 text-[11px] text-[#66717d]">
                                        {{ $attr['occ_adults'] ?? '-' }}
                                    </td>

                                    <td class="px-5 py-4 text-[11px] text-[#66717d]">
                                        {{ $attr['occ_children'] ?? '-' }}
                                    </td>

                                    <td class="px-5 py-4">

                                        <code class="text-[10px] text-[#66717d]">
                                                            {{ $item['id'] ?? $attr['id'] ?? '-' }}
                                                        </code>

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td colspan="5" class="py-12 text-center text-[11px] text-[#929ba4]">
                                        No Room Types found.
                                    </td>

                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>


            {{-- RATE PLANS --}}
            <div class="bg-white border border-[#e2e6ea] rounded-[3px]">

                <div class="px-5 py-4 border-b border-[#edf0f2]">

                    <div class="text-[12px] font-medium">
                        Channex Rate Plans
                    </div>

                    <div class="text-[10px] text-[#9aa2aa] mt-1">
                        Rate Plans loaded directly from Channex
                    </div>

                </div>


                <div class="overflow-x-auto">

                    <table class="w-full">

                        <thead class="bg-[#fafbfc] border-b border-[#e7eaed]">

                            <tr class="text-left text-[10px] uppercase text-[#77828d]">

                                <th class="px-5 py-3 font-medium">
                                    Rate Plan
                                </th>

                                <th class="px-5 py-3 font-medium">
                                    Sell Mode
                                </th>

                                <th class="px-5 py-3 font-medium">
                                    Room Type ID
                                </th>

                                <th class="px-5 py-3 font-medium">
                                    Channex ID
                                </th>

                            </tr>

                        </thead>


                        <tbody class="divide-y divide-[#edf0f2]">

                            @forelse($channexRatePlans as $item)

                                @php
                                    $attr =
                                        $item['attributes'] ?? [];
                                @endphp

                                <tr>

                                    <td class="px-5 py-4 text-[12px] font-medium">
                                        {{ $attr['title'] ?? '-' }}
                                    </td>

                                    <td class="px-5 py-4 text-[11px] text-[#66717d]">
                                        {{ $attr['sell_mode'] ?? '-' }}
                                    </td>

                                    <td class="px-5 py-4">

                                        <code class="text-[10px]">
                                                            {{ $attr['room_type_id'] ?? '-' }}
                                                        </code>

                                    </td>

                                    <td class="px-5 py-4">

                                        <code class="text-[10px]">
                                                            {{ $item['id'] ?? $attr['id'] ?? '-' }}
                                                        </code>

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td colspan="4" class="py-12 text-center text-[11px] text-[#929ba4]">
                                        No Rate Plans found.
                                    </td>

                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>

        @endif

    </div>

@endsection