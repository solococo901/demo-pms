@extends('layouts.pms')

@section('title', 'Add Room - CityHouse PMS')

@section('content')

<div class="max-w-[980px]">

    {{-- ====================================================== --}}
    {{-- HEADER --}}
    {{-- ====================================================== --}}

    <div class="flex items-center justify-between mb-5">

        <div>

            <h1 class="text-[18px] font-medium text-[#303942]">
                Add Room
            </h1>

            <p class="text-[11px] text-[#8a949e] mt-1">
                Create a physical room for {{ $property->name }}
            </p>

        </div>


        <a
            href="{{ route('pms.rooms.index') }}"
            class="h-[34px] px-4 inline-flex items-center border border-[#dce1e6] bg-white rounded-[3px] text-[11px] text-[#5f6a75]"
        >
            ← Back
        </a>

    </div>


    {{-- ====================================================== --}}
    {{-- FORM --}}
    {{-- ====================================================== --}}

    <form
        method="POST"
        action="{{ route('pms.rooms.store') }}"
    >

        @csrf


        <div class="bg-white border border-[#e2e6ea] rounded-[3px]">

            {{-- HEADER --}}
            <div class="px-5 py-4 border-b border-[#edf0f2]">

                <div class="text-[12px] font-medium text-[#36414c]">
                    Room Information
                </div>

                <div class="text-[10px] text-[#929ba4] mt-1">
                    Physical room details used by Front Desk and Housekeeping
                </div>

            </div>


            {{-- CONTENT --}}
            <div class="p-5">

                @include('pms.rooms._form')

            </div>


            {{-- FOOTER --}}
            <div class="px-5 py-4 border-t border-[#edf0f2] bg-[#fafbfc] flex justify-end gap-2">

                <a
                    href="{{ route('pms.rooms.index') }}"
                    class="h-[34px] px-4 inline-flex items-center justify-center border border-[#dce1e6] bg-white rounded-[3px] text-[11px] text-[#5f6a75]"
                >
                    Cancel
                </a>


                <button
                    type="submit"
                    class="h-[34px] px-5 bg-[#1677ff] text-white rounded-[3px] text-[11px] font-medium"
                >
                    Create Room
                </button>

            </div>

        </div>

    </form>

</div>

@endsection