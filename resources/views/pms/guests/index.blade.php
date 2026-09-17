@extends('layouts.pms')

@section('title', 'Guests - CityHouse PMS')

@section('content')

<div class="w-full">

    {{-- HEADER --}}
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between mb-5">

        <div>
            <h1 class="text-[18px] font-medium text-[#303942]">
                Guests
            </h1>

            <p class="text-[11px] text-[#8a949e] mt-1">
                Manage guest profiles and contact information
            </p>
        </div>

        <a
            href="{{ route('pms.guests.create') }}"
            class="h-[34px] px-4 inline-flex items-center justify-center bg-[#1677ff] text-white rounded-[3px] text-[11px] font-medium hover:bg-[#0969da]"
        >
            + Add Guest
        </a>

    </div>


    {{-- MESSAGES --}}
    @if(session('success'))

        <div class="mb-4 px-4 py-3 bg-[#edf9f1] border border-[#cfe9d7] rounded-[3px] text-[11px] text-[#34754c]">
            {{ session('success') }}
        </div>

    @endif


    {{-- PROPERTY SUMMARY --}}
    <div class="bg-white border border-[#e2e6ea] rounded-[3px] mb-4">

        <div class="px-5 py-4 flex items-center justify-between">

            <div>

                <div class="text-[9px] uppercase tracking-wide text-[#98a1aa]">
                    Property
                </div>

                <div class="text-[12px] font-medium text-[#36414c] mt-1">
                    {{ $property->name }}
                </div>

            </div>


            <div>

                <div class="text-[9px] uppercase tracking-wide text-[#98a1aa]">
                    Guests
                </div>

                <div class="text-[16px] font-medium text-[#36414c] mt-1 text-right">
                    {{ $guests->count() }}
                </div>

            </div>

        </div>

    </div>


    {{-- FILTERS --}}
    <form
        method="GET"
        action="{{ route('pms.guests.index') }}"
        class="bg-white border border-[#e2e6ea] rounded-[3px] mb-4"
    >

        <div class="p-4">

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-[1.5fr_1fr_1fr_auto] gap-3">

                {{-- SEARCH --}}
                <div>

                    <label class="block text-[9px] uppercase tracking-wide text-[#8c969f] mb-1.5">
                        Search
                    </label>

                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Name, guest code, phone, email..."
                        class="w-full h-[34px] px-3 border border-[#dce1e6] bg-white rounded-[3px] text-[11px] outline-none focus:border-[#1677ff]"
                    >

                </div>


                {{-- NATIONALITY --}}
                <div>

                    <label class="block text-[9px] uppercase tracking-wide text-[#8c969f] mb-1.5">
                        Nationality
                    </label>

                    <select
                        name="nationality"
                        class="w-full h-[34px] px-3 border border-[#dce1e6] bg-white rounded-[3px] text-[11px]"
                    >

                        <option value="">
                            All Nationalities
                        </option>

                        @foreach($nationalities as $nationality)

                            <option
                                value="{{ $nationality }}"
                                @selected(request('nationality') === $nationality)
                            >
                                {{ $nationality }}
                            </option>

                        @endforeach

                    </select>

                </div>


                {{-- STATUS --}}
                <div>

                    <label class="block text-[9px] uppercase tracking-wide text-[#8c969f] mb-1.5">
                        Status
                    </label>

                    <select
                        name="status"
                        class="w-full h-[34px] px-3 border border-[#dce1e6] bg-white rounded-[3px] text-[11px]"
                    >

                        <option value="">
                            All Status
                        </option>

                        <option value="active" @selected(request('status') === 'active')>
                            Active
                        </option>

                        <option value="inactive" @selected(request('status') === 'inactive')>
                            Inactive
                        </option>

                        <option value="blacklisted" @selected(request('status') === 'blacklisted')>
                            Blacklisted
                        </option>

                    </select>

                </div>


                {{-- ACTIONS --}}
                <div class="flex items-end gap-2">

                    <button
                        type="submit"
                        class="h-[34px] px-4 bg-[#1677ff] text-white rounded-[3px] text-[10px]"
                    >
                        Filter
                    </button>

                    <a
                        href="{{ route('pms.guests.index') }}"
                        class="h-[34px] px-3 inline-flex items-center border border-[#dce1e6] rounded-[3px] text-[10px] text-[#65707b]"
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

            <table class="w-full min-w-[1050px]">

                <thead class="bg-[#fafbfc] border-b border-[#e7eaed]">

                    <tr class="text-left text-[9px] uppercase tracking-wide text-[#77828d]">

                        <th class="px-5 py-3 font-medium">
                            Guest Code
                        </th>

                        <th class="px-5 py-3 font-medium">
                            Guest
                        </th>

                        <th class="px-5 py-3 font-medium">
                            Contact
                        </th>

                        <th class="px-5 py-3 font-medium">
                            Nationality
                        </th>

                        <th class="px-5 py-3 font-medium">
                            ID Document
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

                    @forelse($guests as $guest)

                        <tr class="hover:bg-[#fafcff]">

                            {{-- CODE --}}
                            <td class="px-5 py-4">

                                <div class="text-[11px] font-medium text-[#1677ff]">
                                    {{ $guest->code }}
                                </div>

                            </td>


                            {{-- NAME --}}
                            <td class="px-5 py-4">

                                <div class="text-[12px] font-medium text-[#303942]">
                                    {{ $guest->full_name }}
                                </div>

                                @if($guest->date_of_birth)

                                    <div class="text-[9px] text-[#9ba4ad] mt-1">
                                        DOB:
                                        {{ $guest->date_of_birth->format('d/m/Y') }}
                                    </div>

                                @endif

                            </td>


                            {{-- CONTACT --}}
                            <td class="px-5 py-4">

                                <div class="text-[10px] text-[#56616c]">
                                    {{ $guest->phone ?: '-' }}
                                </div>

                                <div class="text-[9px] text-[#969fa8] mt-1">
                                    {{ $guest->email ?: '-' }}
                                </div>

                            </td>


                            {{-- NATIONALITY --}}
                            <td class="px-5 py-4 text-[10px] text-[#66717d]">
                                {{ $guest->nationality ?: '-' }}
                            </td>


                            {{-- ID --}}
                            <td class="px-5 py-4">

                                @if($guest->id_number)

                                    <div class="text-[10px] text-[#56616c]">
                                        {{ $guest->id_number }}
                                    </div>

                                    <div class="text-[9px] text-[#9ba4ad] mt-1">
                                        {{ ucwords(str_replace('_', ' ', $guest->id_type ?? '')) }}
                                    </div>

                                @else

                                    <span class="text-[10px] text-[#a0a8b0]">
                                        -
                                    </span>

                                @endif

                            </td>


                            {{-- STATUS --}}
                            <td class="px-5 py-4">

                                @if($guest->status === 'active')

                                    <span class="inline-flex items-center gap-1.5 px-2 py-1 bg-[#edf9f1] text-[#31845b] rounded-[3px] text-[9px]">
                                        <span class="w-[5px] h-[5px] rounded-full bg-[#3fb76f]"></span>
                                        Active
                                    </span>

                                @elseif($guest->status === 'inactive')

                                    <span class="inline-flex items-center gap-1.5 px-2 py-1 bg-[#f3f4f5] text-[#747d86] rounded-[3px] text-[9px]">
                                        <span class="w-[5px] h-[5px] rounded-full bg-[#aeb5bc]"></span>
                                        Inactive
                                    </span>

                                @else

                                    <span class="inline-flex items-center gap-1.5 px-2 py-1 bg-[#fff0f0] text-[#b64646] rounded-[3px] text-[9px]">
                                        <span class="w-[5px] h-[5px] rounded-full bg-[#d95c5c]"></span>
                                        Blacklisted
                                    </span>

                                @endif

                            </td>


                            {{-- ACTIONS --}}
                            <td class="px-5 py-4">

                                <div class="flex justify-end items-center gap-3">

                                    <a
                                        href="{{ route('pms.guests.edit', $guest) }}"
                                        class="text-[10px] text-[#1677ff] hover:underline"
                                    >
                                        Edit
                                    </a>


                                    <form
                                        method="POST"
                                        action="{{ route('pms.guests.destroy', $guest) }}"
                                        onsubmit="return confirm('Delete guest {{ $guest->full_name }}?')"
                                    >

                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            class="text-[10px] text-[#c04b4b] hover:underline"
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
                                colspan="7"
                                class="py-16 text-center"
                            >

                                <div class="text-[12px] text-[#76818c]">
                                    No guests found
                                </div>

                                <div class="text-[10px] text-[#a0a8b0] mt-1">
                                    Create your first guest profile.
                                </div>

                                <a
                                    href="{{ route('pms.guests.create') }}"
                                    class="inline-flex mt-4 h-[32px] px-4 items-center bg-[#1677ff] text-white rounded-[3px] text-[10px]"
                                >
                                    + Add Guest
                                </a>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        <div class="px-5 py-3 border-t border-[#edf0f2] bg-[#fafbfc] text-[9px] text-[#929ba4]">
            {{ $guests->count() }} guest(s) displayed
        </div>

    </div>

</div>

@endsection