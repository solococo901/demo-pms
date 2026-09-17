@php
    $roomLine = isset($reservation)
        ? $reservation->rooms->first()
        : null;

    $selectedRoomType = old(
        'room_type_id',
        $roomLine?->room_type_id ?? ''
    );

    $selectedRatePlan = old(
        'rate_plan_id',
        $roomLine?->rate_plan_id ?? ''
    );
@endphp


<div
    x-data="reservationForm({
        roomTypeId: @js((string) $selectedRoomType),
        ratePlanId: @js((string) $selectedRatePlan),
        checkIn: @js(old('check_in', $roomLine?->check_in ?? now()->format('Y-m-d'))),
        checkOut: @js(old('check_out', $roomLine?->check_out ?? now()->addDay()->format('Y-m-d'))),
        nightlyRate: @js((float) old('nightly_rate', $roomLine?->nightly_rate ?? 0)),
        taxAmount: @js((float) old('tax_amount', $reservation->tax_amount ?? 0)),
        feeAmount: @js((float) old('fee_amount', $reservation->fee_amount ?? 0)),
        ratePlans: @js(
            $ratePlans->map(fn ($plan) => [
                'id' => (string) $plan->id,
                'room_type_id' => (string) $plan->room_type_id,
                'name' => $plan->name,
                'code' => $plan->code,
                'base_rate' => (float) $plan->base_rate,
            ])->values()
        )
    })"
    class="space-y-6"
>

    {{-- GUEST --}}
    <div>

        <div class="text-[10px] uppercase tracking-wide text-[#8b949d] font-medium mb-4">
            Guest
        </div>

        <div>

            <label class="block text-[10px] font-medium text-[#59636e] mb-2">
                Primary Guest
                <span class="text-red-500">*</span>
            </label>

            <select
                name="guest_id"
                class="w-full h-[38px] px-3 bg-white border border-[#dce1e6] rounded-[3px] text-[12px]"
            >

                <option value="">
                    Select Guest
                </option>

                @foreach($guests as $guest)

                    <option
                        value="{{ $guest->id }}"
                        @selected((string) old('guest_id', $reservation->guest_id ?? '') === (string) $guest->id)
                    >
                        {{ $guest->code }} — {{ $guest->full_name }}
                        @if($guest->phone)
                            — {{ $guest->phone }}
                        @endif
                    </option>

                @endforeach

            </select>


            <div class="mt-2">

                <a
                    href="{{ route('pms.guests.create') }}"
                    class="text-[10px] text-[#1677ff]"
                >
                    + Create new Guest
                </a>

            </div>

        </div>

    </div>


    {{-- STAY --}}
    <div class="pt-5 border-t border-[#edf0f2]">

        <div class="text-[10px] uppercase tracking-wide text-[#8b949d] font-medium mb-4">
            Stay Information
        </div>


        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

            <div>

                <label class="block text-[10px] font-medium text-[#59636e] mb-2">
                    Check-in
                    <span class="text-red-500">*</span>
                </label>

                <input
                    type="date"
                    name="check_in"
                    x-model="checkIn"
                    class="w-full h-[38px] px-3 border border-[#dce1e6] rounded-[3px] text-[12px]"
                >

            </div>


            <div>

                <label class="block text-[10px] font-medium text-[#59636e] mb-2">
                    Check-out
                    <span class="text-red-500">*</span>
                </label>

                <input
                    type="date"
                    name="check_out"
                    x-model="checkOut"
                    class="w-full h-[38px] px-3 border border-[#dce1e6] rounded-[3px] text-[12px]"
                >

            </div>


            <div>

                <label class="block text-[10px] font-medium text-[#59636e] mb-2">
                    Room Type
                    <span class="text-red-500">*</span>
                </label>

                <select
                    name="room_type_id"
                    x-model="roomTypeId"
                    @change="roomTypeChanged()"
                    class="w-full h-[38px] px-3 bg-white border border-[#dce1e6] rounded-[3px] text-[12px]"
                >

                    <option value="">
                        Select Room Type
                    </option>

                    @foreach($roomTypes as $roomType)

                        <option value="{{ $roomType->id }}">
                            {{ $roomType->name }} — {{ $roomType->code }}
                        </option>

                    @endforeach

                </select>

            </div>


            <div>

                <label class="block text-[10px] font-medium text-[#59636e] mb-2">
                    Rate Plan
                </label>

                <select
                    name="rate_plan_id"
                    x-model="ratePlanId"
                    @change="ratePlanChanged()"
                    class="w-full h-[38px] px-3 bg-white border border-[#dce1e6] rounded-[3px] text-[12px]"
                >

                    <option value="">
                        No Rate Plan
                    </option>

                    <template x-for="plan in filteredRatePlans" :key="plan.id">

                        <option
                            :value="plan.id"
                            x-text="plan.name + ' — ' + plan.code"
                        ></option>

                    </template>

                </select>

            </div>


            <div>

                <label class="block text-[10px] font-medium text-[#59636e] mb-2">
                    Adults
                </label>

                <input
                    type="number"
                    name="adults"
                    min="1"
                    value="{{ old('adults', $roomLine?->adults ?? 1) }}"
                    class="w-full h-[38px] px-3 border border-[#dce1e6] rounded-[3px] text-[12px]"
                >

            </div>


            <div>

                <label class="block text-[10px] font-medium text-[#59636e] mb-2">
                    Children
                </label>

                <input
                    type="number"
                    name="children"
                    min="0"
                    value="{{ old('children', $roomLine?->children ?? 0) }}"
                    class="w-full h-[38px] px-3 border border-[#dce1e6] rounded-[3px] text-[12px]"
                >

            </div>

        </div>

    </div>


    {{-- RATE --}}
    <div class="pt-5 border-t border-[#edf0f2]">

        <div class="text-[10px] uppercase tracking-wide text-[#8b949d] font-medium mb-4">
            Pricing
        </div>


        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">

            <div>

                <label class="block text-[10px] font-medium text-[#59636e] mb-2">
                    Nightly Rate
                </label>

                <input
                    type="number"
                    name="nightly_rate"
                    x-model.number="nightlyRate"
                    min="0"
                    step="1"
                    class="w-full h-[38px] px-3 border border-[#dce1e6] rounded-[3px] text-[12px]"
                >

            </div>


            <div>

                <label class="block text-[10px] font-medium text-[#59636e] mb-2">
                    Tax Amount
                </label>

                <input
                    type="number"
                    name="tax_amount"
                    x-model.number="taxAmount"
                    min="0"
                    step="1"
                    class="w-full h-[38px] px-3 border border-[#dce1e6] rounded-[3px] text-[12px]"
                >

            </div>


            <div>

                <label class="block text-[10px] font-medium text-[#59636e] mb-2">
                    Fee Amount
                </label>

                <input
                    type="number"
                    name="fee_amount"
                    x-model.number="feeAmount"
                    min="0"
                    step="1"
                    class="w-full h-[38px] px-3 border border-[#dce1e6] rounded-[3px] text-[12px]"
                >

            </div>

        </div>


        {{-- PRICE SUMMARY --}}
        <div class="mt-5 bg-[#fafbfc] border border-[#e7eaed] rounded-[3px]">

            <div class="grid grid-cols-2 md:grid-cols-4">

                <div class="p-4 border-r border-[#edf0f2]">

                    <div class="text-[9px] uppercase text-[#929ba4]">
                        Nights
                    </div>

                    <div
                        class="text-[16px] font-medium text-[#36414c] mt-1"
                        x-text="nights"
                    ></div>

                </div>


                <div class="p-4 border-r border-[#edf0f2]">

                    <div class="text-[9px] uppercase text-[#929ba4]">
                        Room Total
                    </div>

                    <div
                        class="text-[13px] font-medium text-[#36414c] mt-1"
                        x-text="money(roomTotal)"
                    ></div>

                </div>


                <div class="p-4 border-r border-[#edf0f2]">

                    <div class="text-[9px] uppercase text-[#929ba4]">
                        Tax + Fee
                    </div>

                    <div
                        class="text-[13px] font-medium text-[#36414c] mt-1"
                        x-text="money(extraTotal)"
                    ></div>

                </div>


                <div class="p-4">

                    <div class="text-[9px] uppercase text-[#929ba4]">
                        Total
                    </div>

                    <div
                        class="text-[16px] font-medium text-[#1677ff] mt-1"
                        x-text="money(grandTotal)"
                    ></div>

                </div>

            </div>

        </div>

    </div>


    {{-- SOURCE --}}
    <div class="pt-5 border-t border-[#edf0f2]">

        <div class="text-[10px] uppercase tracking-wide text-[#8b949d] font-medium mb-4">
            Booking Information
        </div>


        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

            <div>

                <label class="block text-[10px] font-medium text-[#59636e] mb-2">
                    Source
                </label>

                <select
                    name="source"
                    class="w-full h-[38px] px-3 bg-white border border-[#dce1e6] rounded-[3px] text-[12px]"
                >

                    <option value="direct" @selected(old('source', $reservation->source ?? 'direct') === 'direct')>Direct</option>
                    <option value="website" @selected(old('source', $reservation->source ?? '') === 'website')>Website</option>
                    <option value="walk_in" @selected(old('source', $reservation->source ?? '') === 'walk_in')>Walk In</option>
                    <option value="phone" @selected(old('source', $reservation->source ?? '') === 'phone')>Phone</option>
                    <option value="channex" @selected(old('source', $reservation->source ?? '') === 'channex')>Channex</option>
                    <option value="ota" @selected(old('source', $reservation->source ?? '') === 'ota')>OTA</option>

                </select>

            </div>


            <div>

                <label class="block text-[10px] font-medium text-[#59636e] mb-2">
                    Channel
                </label>

                <input
                    type="text"
                    name="channel"
                    value="{{ old('channel', $reservation->channel ?? '') }}"
                    placeholder="Booking.com, Agoda..."
                    class="w-full h-[38px] px-3 border border-[#dce1e6] rounded-[3px] text-[12px]"
                >

            </div>


            <div>

                <label class="block text-[10px] font-medium text-[#59636e] mb-2">
                    External Reservation ID
                </label>

                <input
                    type="text"
                    name="external_reservation_id"
                    value="{{ old('external_reservation_id', $reservation->external_reservation_id ?? '') }}"
                    class="w-full h-[38px] px-3 border border-[#dce1e6] rounded-[3px] text-[12px]"
                >

            </div>


            <div>

                <label class="block text-[10px] font-medium text-[#59636e] mb-2">
                    Reservation Status
                </label>

                <select
                    name="status"
                    class="w-full h-[38px] px-3 bg-white border border-[#dce1e6] rounded-[3px] text-[12px]"
                >

                    <option value="pending" @selected(old('status', $reservation->status ?? 'confirmed') === 'pending')>Pending</option>
                    <option value="confirmed" @selected(old('status', $reservation->status ?? 'confirmed') === 'confirmed')>Confirmed</option>
                    <option value="checked_in" @selected(old('status', $reservation->status ?? '') === 'checked_in')>Checked In</option>
                    <option value="checked_out" @selected(old('status', $reservation->status ?? '') === 'checked_out')>Checked Out</option>
                    <option value="cancelled" @selected(old('status', $reservation->status ?? '') === 'cancelled')>Cancelled</option>
                    <option value="no_show" @selected(old('status', $reservation->status ?? '') === 'no_show')>No Show</option>

                </select>

            </div>

        </div>

    </div>


    {{-- NOTES --}}
    <div class="pt-5 border-t border-[#edf0f2] grid grid-cols-1 md:grid-cols-2 gap-5">

        <div>

            <label class="block text-[10px] font-medium text-[#59636e] mb-2">
                Special Requests
            </label>

            <textarea
                name="special_requests"
                rows="4"
                class="w-full px-3 py-3 border border-[#dce1e6] rounded-[3px] text-[12px]"
            >{{ old('special_requests', $reservation->special_requests ?? '') }}</textarea>

        </div>


        <div>

            <label class="block text-[10px] font-medium text-[#59636e] mb-2">
                Internal Notes
            </label>

            <textarea
                name="notes"
                rows="4"
                class="w-full px-3 py-3 border border-[#dce1e6] rounded-[3px] text-[12px]"
            >{{ old('notes', $reservation->notes ?? '') }}</textarea>

        </div>

    </div>


    @if($errors->any())

        <div class="px-4 py-3 bg-[#fff3f3] border border-[#f1cccc] rounded-[3px]">

            @foreach($errors->all() as $error)

                <div class="text-[10px] text-[#b64646]">
                    {{ $error }}
                </div>

            @endforeach

        </div>

    @endif

</div>


<script>
    function reservationForm(config) {
        return {
            roomTypeId: config.roomTypeId || '',
            ratePlanId: config.ratePlanId || '',
            checkIn: config.checkIn || '',
            checkOut: config.checkOut || '',
            nightlyRate: Number(config.nightlyRate || 0),
            taxAmount: Number(config.taxAmount || 0),
            feeAmount: Number(config.feeAmount || 0),
            ratePlans: config.ratePlans || [],

            get filteredRatePlans() {
                if (!this.roomTypeId) {
                    return [];
                }

                return this.ratePlans.filter(
                    plan =>
                        String(plan.room_type_id) ===
                        String(this.roomTypeId)
                );
            },

            get nights() {
                if (!this.checkIn || !this.checkOut) {
                    return 0;
                }

                const start = new Date(
                    this.checkIn + 'T00:00:00'
                );

                const end = new Date(
                    this.checkOut + 'T00:00:00'
                );

                const difference =
                    end.getTime()
                    -
                    start.getTime();

                const days =
                    Math.round(
                        difference
                        /
                        86400000
                    );

                return days > 0
                    ? days
                    : 0;
            },

            get roomTotal() {
                return this.nights
                    *
                    Number(
                        this.nightlyRate || 0
                    );
            },

            get extraTotal() {
                return Number(
                    this.taxAmount || 0
                )
                +
                Number(
                    this.feeAmount || 0
                );
            },

            get grandTotal() {
                return this.roomTotal
                    +
                    this.extraTotal;
            },

            roomTypeChanged() {
                const currentPlan =
                    this.ratePlans.find(
                        plan =>
                            String(plan.id)
                            ===
                            String(this.ratePlanId)
                    );

                if (
                    !currentPlan
                    ||
                    String(currentPlan.room_type_id)
                    !==
                    String(this.roomTypeId)
                ) {
                    this.ratePlanId = '';
                }
            },

            ratePlanChanged() {
                const plan =
                    this.ratePlans.find(
                        item =>
                            String(item.id)
                            ===
                            String(this.ratePlanId)
                    );

                if (plan) {
                    this.nightlyRate =
                        Number(
                            plan.base_rate || 0
                        );
                }
            },

            money(value) {
                return new Intl.NumberFormat(
                    'vi-VN'
                ).format(
                    Number(value || 0)
                ) + ' ₫';
            },
        }
    }
</script>