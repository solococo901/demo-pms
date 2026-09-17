@extends('layouts.pms')

@section('title', 'Create Rate Plan - CityHouse PMS')

@section('content')

<div class="max-w-[1000px]">

    <div class="flex items-center justify-between mb-5">

        <div>
            <h1 class="text-[18px] font-medium">
                Create Rate Plan
            </h1>

            <p class="text-[11px] text-[#8a949e] mt-1">
                {{ $property->name }}
            </p>
        </div>

        <a
            href="{{ route('pms.rate-plans.index') }}"
            class="h-[34px] px-4 inline-flex items-center border border-[#dce1e6] bg-white rounded-[3px] text-[11px]"
        >
            Back
        </a>

    </div>


    <form
        method="POST"
        action="{{ route('pms.rate-plans.store') }}"
    >

        @csrf

        <div class="bg-white border border-[#e2e6ea] rounded-[3px]">

            <div class="px-5 py-4 border-b border-[#edf0f2]">

                <div class="text-[12px] font-medium">
                    Rate Plan Information
                </div>

            </div>

            <div class="p-5">

                @include('pms.rate-plans._form')

            </div>

            <div class="px-5 py-4 border-t border-[#edf0f2] flex justify-end gap-2">

                <a
                    href="{{ route('pms.rate-plans.index') }}"
                    class="h-[34px] px-4 inline-flex items-center border border-[#dce1e6] rounded-[3px] text-[11px]"
                >
                    Cancel
                </a>

                <button
                    class="h-[34px] px-4 bg-[#1677ff] text-white rounded-[3px] text-[11px]"
                >
                    Create Rate Plan
                </button>

            </div>

        </div>

    </form>

</div>

@endsection