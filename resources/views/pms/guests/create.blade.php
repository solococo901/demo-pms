@extends('layouts.pms')

@section('title', 'Add Guest - CityHouse PMS')

@section('content')

<div class="max-w-[980px]">

    <div class="flex items-center justify-between mb-5">

        <div>

            <h1 class="text-[18px] font-medium text-[#303942]">
                Add Guest
            </h1>

            <p class="text-[11px] text-[#8a949e] mt-1">
                Create a new guest profile
            </p>

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
        action="{{ route('pms.guests.store') }}"
    >

        @csrf


        <div class="bg-white border border-[#e2e6ea] rounded-[3px]">

            <div class="px-5 py-4 border-b border-[#edf0f2]">

                <div class="text-[12px] font-medium text-[#36414c]">
                    Guest Profile
                </div>

                <div class="text-[10px] text-[#929ba4] mt-1">
                    Guest code will be generated automatically.
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
                    Create Guest
                </button>

            </div>

        </div>

    </form>

</div>

@endsection