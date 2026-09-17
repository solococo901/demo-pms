@extends('layouts.pms')

@section('title', 'Room Types - CityHouse PMS')

@section('content')

<div>

    {{-- HEADER --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-5">

        <div>

            <h1 class="text-[18px] font-medium">
                Room Types
            </h1>

            <p class="text-[11px] text-[#8a949e] mt-1">
                Manage room categories for {{ $property->name }}
            </p>

        </div>


        <a
            href="{{ route('pms.room-types.create') }}"
            class="h-[34px] px-4 inline-flex items-center justify-center bg-[#1677ff] text-white rounded-[3px] text-[11px] font-medium hover:bg-[#0969da]"
        >
            + Create Room Type
        </a>

    </div>


    {{-- MESSAGE --}}
    @if(session('success'))

        <div class="mb-4 px-4 py-3 bg-[#edf9f1] border border-[#cfe9d7] text-[#34754c] text-[11px] rounded-[3px]">

            {{ session('success') }}

        </div>

    @endif


    {{-- TABLE --}}
    <div class="bg-white border border-[#e2e6ea] rounded-[3px]">

        {{-- TOOLBAR --}}
        <div class="p-3 border-b border-[#edf0f2]">

            <form
                method="GET"
                class="flex items-center gap-2"
            >

                <div class="relative w-[260px]">

                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search..."
                        class="w-full h-[34px] px-3 pr-9 border border-[#dce1e6] rounded-[3px] text-[11px] outline-none focus:border-[#1677ff]"
                    >

                    <button
                        type="submit"
                        class="absolute right-0 top-0 h-[34px] w-[36px] flex items-center justify-center text-[#7c8792]"
                    >
                        ⌕
                    </button>

                </div>

            </form>

        </div>


        <div class="overflow-x-auto">

            <table class="w-full">

                <thead class="bg-[#fafbfc] border-b border-[#e7eaed]">

                <tr class="text-left text-[10px] uppercase tracking-wide text-[#77828d]">

                    <th class="px-5 py-3 font-medium">
                        Title
                    </th>

                    <th class="px-5 py-3 font-medium">
                        Code
                    </th>

                    <th class="px-5 py-3 font-medium">
                        Rooms
                    </th>

                    <th class="px-5 py-3 font-medium">
                        Occupancy
                    </th>

                    <th class="px-5 py-3 font-medium">
                        Base Rate
                    </th>

                    <th class="px-5 py-3 font-medium">
                        Channex
                    </th>

                    <th class="px-5 py-3 font-medium">
                        Status
                    </th>

                    <th class="px-5 py-3 font-medium text-right">
                        Actions
                    </th>

                </tr>

                </thead>


                <tbody class="divide-y divide-[#edf0f2]">

                @forelse($roomTypes as $roomType)

                    <tr class="hover:bg-[#fafcff]">

                        <td class="px-5 py-4">

                            <div class="text-[12px] font-medium text-[#36414c]">
                                {{ $roomType->name }}
                            </div>

                            @if($roomType->description)

                                <div class="text-[10px] text-[#98a1aa] mt-1 max-w-[300px] truncate">
                                    {{ $roomType->description }}
                                </div>

                            @endif

                        </td>


                        <td class="px-5 py-4 text-[11px] text-[#66717d]">
                            {{ $roomType->code }}
                        </td>


                        <td class="px-5 py-4 text-[11px] text-[#66717d]">
                            {{ $roomType->total_rooms }}
                        </td>


                        <td class="px-5 py-4 text-[11px] text-[#66717d]">

                            {{ $roomType->max_adults }} Adults

                            @if($roomType->max_children > 0)
                                + {{ $roomType->max_children }} Children
                            @endif

                        </td>


                        <td class="px-5 py-4 text-[11px] text-[#66717d]">

                            {{ number_format(
                                $roomType->base_price,
                                0,
                                ',',
                                '.'
                            ) }} ₫

                        </td>


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


                        <td class="px-5 py-4">

                            @if($roomType->status === 'active')

                                <span class="inline-flex items-center gap-2 text-[10px] text-[#31845b]">

                                    <span class="w-[6px] h-[6px] bg-[#3fb76f] rounded-full"></span>

                                    Active

                                </span>

                            @else

                                <span class="text-[10px] text-[#8b949e]">
                                    Inactive
                                </span>

                            @endif

                        </td>


                        <td class="px-5 py-4">

                            <div class="flex items-center justify-end gap-3">

                                <a
                                    href="{{ route(
                                        'pms.room-types.edit',
                                        $roomType
                                    ) }}"
                                    class="text-[11px] text-[#1677ff] hover:underline"
                                >
                                    Edit
                                </a>


                                <form
                                    method="POST"
                                    action="{{ route(
                                        'pms.room-types.destroy',
                                        $roomType
                                    ) }}"
                                    onsubmit="return confirm('Delete this room type?')"
                                >

                                    @csrf
                                    @method('DELETE')

                                    <button
                                        type="submit"
                                        class="text-[11px] text-[#dc5b5b] hover:underline"
                                    >
                                        Delete
                                    </button>

                                </form>

                            </div>

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td
                            colspan="8"
                            class="py-16 text-center"
                        >

                            <div class="text-[12px] text-[#697581]">
                                No room types
                            </div>

                            <div class="text-[10px] text-[#9ba4ad] mt-1">
                                Create your first room type.
                            </div>

                            <a
                                href="{{ route('pms.room-types.create') }}"
                                class="inline-flex mt-4 h-[32px] px-4 items-center bg-[#1677ff] text-white rounded-[3px] text-[10px]"
                            >
                                + Create Room Type
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