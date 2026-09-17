@extends('layouts.pms')

@section('title', 'Rate Plans - CityHouse PMS')

@section('content')

<div>

    <div class="flex items-center justify-between mb-5">

        <div>

            <h1 class="text-[18px] font-medium">
                Rate Plans
            </h1>

            <p class="text-[11px] text-[#8a949e] mt-1">
                Manage room pricing plans
            </p>

        </div>

        <a
            href="{{ route('pms.rate-plans.create') }}"
            class="h-[34px] px-4 inline-flex items-center bg-[#1677ff] text-white rounded-[3px] text-[11px]"
        >
            + Create Rate Plan
        </a>

    </div>


    @if(session('success'))

        <div class="mb-4 px-4 py-3 bg-[#edf9f1] border border-[#cfe9d7] text-[#34754c] text-[11px]">
            {{ session('success') }}
        </div>

    @endif


    <div class="bg-white border border-[#e2e6ea] rounded-[3px] overflow-x-auto">

        <table class="w-full">

            <thead class="bg-[#fafbfc] border-b border-[#e7eaed]">

            <tr class="text-left text-[10px] uppercase tracking-wide text-[#77828d]">

                <th class="px-5 py-3 font-medium">
                    Rate Plan
                </th>

                <th class="px-5 py-3 font-medium">
                    Room Type
                </th>

                <th class="px-5 py-3 font-medium">
                    Code
                </th>

                <th class="px-5 py-3 font-medium">
                    Base Rate
                </th>

                <th class="px-5 py-3 font-medium">
                    Min Stay
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

            @forelse($ratePlans as $ratePlan)

                <tr class="hover:bg-[#fafcff]">

                    <td class="px-5 py-4 text-[12px] font-medium">
                        {{ $ratePlan->name }}
                    </td>

                    <td class="px-5 py-4 text-[11px] text-[#66717d]">
                        {{ $ratePlan->roomType?->name }}
                    </td>

                    <td class="px-5 py-4">
                        <code class="text-[10px]">
                            {{ $ratePlan->code }}
                        </code>
                    </td>

                    <td class="px-5 py-4 text-[11px]">
                        {{ number_format($ratePlan->base_rate, 0, ',', '.') }} ₫
                    </td>

                    <td class="px-5 py-4 text-[11px]">
                        {{ $ratePlan->min_stay }}
                    </td>

                    <td class="px-5 py-4">

                        @if($ratePlan->channex_rate_plan_id)

                            <span class="inline-flex items-center gap-2 text-[10px] text-[#31845b]">
                                <span class="w-[6px] h-[6px] bg-[#3fb76f] rounded-full"></span>
                                Mapped
                            </span>

                        @else

                            <span class="text-[10px] text-[#929ba4]">
                                Not mapped
                            </span>

                        @endif

                    </td>

                    <td class="px-5 py-4 text-[10px]">
                        {{ ucfirst($ratePlan->status) }}
                    </td>

                    <td class="px-5 py-4">

                        <div class="flex justify-end gap-3">

                            <a
                                href="{{ route('pms.rate-plans.edit', $ratePlan) }}"
                                class="text-[11px] text-[#1677ff]"
                            >
                                Edit
                            </a>

                            <form
                                method="POST"
                                action="{{ route('pms.rate-plans.destroy', $ratePlan) }}"
                                onsubmit="return confirm('Delete this Rate Plan?')"
                            >

                                @csrf
                                @method('DELETE')

                                <button class="text-[11px] text-red-500">
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
                        class="py-16 text-center text-[11px] text-[#929ba4]"
                    >
                        No Rate Plans yet.
                    </td>

                </tr>

            @endforelse

            </tbody>

        </table>

    </div>

</div>

@endsection