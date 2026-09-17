@extends('layouts.pms')

@section('title', 'Edit Guest - CityHouse PMS')

@section('content')

<div class="max-w-[980px]">

    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between mb-5">

        <div>

            <div class="flex items-center gap-3">

                <h1 class="text-[18px] font-medium text-[#303942]">
                    {{ $guest->full_name }}
                </h1>


                @if($guest->status === 'active')

                    <span class="px-2 py-1 bg-[#edf9f1] text-[#31845b] rounded-[3px] text-[9px]">
                        Active
                    </span>

                @elseif($guest->status === 'inactive')

                    <span class="px-2 py-1 bg-[#f3f4f5] text-[#747d86] rounded-[3px] text-[9px]">
                        Inactive
                    </span>

                @else

                    <span class="px-2 py-1 bg-[#fff0f0] text-[#b64646] rounded-[3px] text-[9px]">
                        Blacklisted
                    </span>

                @endif

            </div>


            <div class="text-[10px] text-[#1677ff] mt-1">
                {{ $guest->code }}
            </div>

        </div>


        <a
            href="{{ route('pms.guests.index') }}"
            class="h-[34px] px-4 inline-flex items-center border border-[#dce1e6] bg-white rounded-[3px] text-[11px]"
        >
            ← Back
        </a>

    </div>


    <form
        method="POST"
        action="{{ route('pms.guests.update', $guest) }}"
    >

        @csrf
        @method('PUT')


        <div class="bg-white border border-[#e2e6ea] rounded-[3px]">

            <div class="px-5 py-4 border-b border-[#edf0f2]">

                <div class="text-[12px] font-medium text-[#36414c]">
                    Guest Profile
                </div>

                <div class="text-[10px] text-[#929ba4] mt-1">
                    Update guest personal and contact information
                </div>

            </div>


            <div class="p-5">

                @include('pms.guests._form')

            </div>


            <div class="px-5 py-4 border-t border-[#edf0f2] bg-[#fafbfc] flex justify-end gap-2">

                <a
                    href="{{ route('pms.guests.index') }}"
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

    </form>

</div>

@endsection