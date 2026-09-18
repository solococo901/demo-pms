@extends('layouts.pms')

@section('title', $reservation->code . ' - CityHouse PMS')

@section('content')

@php
    $checkIn = $reservationRoom
        ? \Carbon\CarbonImmutable::parse($reservationRoom->check_in)
        : null;

    $checkOut = $reservationRoom
        ? \Carbon\CarbonImmutable::parse($reservationRoom->check_out)
        : null;

    $nights = ($checkIn && $checkOut)
        ? $checkIn->diffInDays($checkOut)
        : 0;

    $currency = $reservation->currency ?: 'VND';

    $timezone = $property->timezone ?? 'Asia/Ho_Chi_Minh';

    $defaultPaidAt = \Carbon\CarbonImmutable::now($timezone)
        ->format('Y-m-d\TH:i');

    $defaultRefundedAt = \Carbon\CarbonImmutable::now($timezone)
        ->format('Y-m-d\TH:i');

    $defaultFolioPostedAt = \Carbon\CarbonImmutable::now($timezone)
        ->format('Y-m-d\TH:i');

    $activeFolioItems = $reservation->folioItems
        ->where('status', 'active');

    $extraChargesTotal = (float) $activeFolioItems
        ->sum('total_amount');

    $baseReservationTotal =
        (float) $reservation->subtotal
        + (float) $reservation->tax_amount
        + (float) $reservation->fee_amount;


    /*
    |--------------------------------------------------------------------------
    | Payment Summary - Source of Truth
    |--------------------------------------------------------------------------
    |
    | View luôn tính trực tiếp từ transaction thực tế để tránh trường hợp
    | Reservation.paid_amount hoặc biến truyền từ Controller bị cũ.
    |
    | Gross Paid = tổng Payment completed
    | Refunded   = tổng Refund completed
    | Net Paid   = Gross Paid - Refunded
    | Balance    = Total - Net Paid
    |
    */

    $displayGrossPaid = (float) $reservation
        ->payments
        ->where('status', 'completed')
        ->sum('amount');

    $displayRefundedAmount = (float) $reservation
        ->refunds
        ->where('status', 'completed')
        ->sum('amount');

    $paidAmount = max(
        0,
        $displayGrossPaid - $displayRefundedAmount
    );

    $totalAmount = (float) $reservation->total_amount;

    $outstandingBalance = max(
        0,
        $totalAmount - $paidAmount
    );

    if ($paidAmount <= 0) {
        $paymentStatus = (
            $displayGrossPaid > 0
            &&
            $displayRefundedAmount > 0
        )
            ? 'refunded'
            : 'unpaid';
    } elseif ($paidAmount < $totalAmount) {
        $paymentStatus = 'partial';
    } else {
        $paymentStatus = 'paid';
    }
@endphp


<div class="w-full max-w-[1280px] mx-auto">

    {{-- ====================================================== --}}
    {{-- HEADER --}}
    {{-- ====================================================== --}}

    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between mb-5">

        <div>

            <div class="flex flex-wrap items-center gap-3">

                <h1 class="text-[18px] font-medium text-[#303942]">
                    {{ $reservation->code }}
                </h1>


                @if($reservation->status === 'confirmed')

                    <span class="inline-flex items-center gap-1.5 px-2 py-1 bg-[#eef5ff] text-[#1677ff] rounded-[3px] text-[9px]">
                        <span class="w-[5px] h-[5px] rounded-full bg-[#1677ff]"></span>
                        Confirmed
                    </span>

                @elseif($reservation->status === 'pending')

                    <span class="inline-flex items-center gap-1.5 px-2 py-1 bg-[#fff7e8] text-[#9a6c28] rounded-[3px] text-[9px]">
                        <span class="w-[5px] h-[5px] rounded-full bg-[#d8a348]"></span>
                        Pending
                    </span>

                @elseif($reservation->status === 'checked_in')

                    <span class="inline-flex items-center gap-1.5 px-2 py-1 bg-[#edf9f1] text-[#31845b] rounded-[3px] text-[9px]">
                        <span class="w-[5px] h-[5px] rounded-full bg-[#3fb76f]"></span>
                        Checked In
                    </span>

                @elseif($reservation->status === 'checked_out')

                    <span class="inline-flex items-center gap-1.5 px-2 py-1 bg-[#f1f3f5] text-[#68737e] rounded-[3px] text-[9px]">
                        <span class="w-[5px] h-[5px] rounded-full bg-[#8c969f]"></span>
                        Checked Out
                    </span>

                @elseif($reservation->status === 'cancelled')

                    <span class="inline-flex items-center gap-1.5 px-2 py-1 bg-[#fff0f0] text-[#b64646] rounded-[3px] text-[9px]">
                        <span class="w-[5px] h-[5px] rounded-full bg-[#d95c5c]"></span>
                        Cancelled
                    </span>

                @elseif($reservation->status === 'no_show')

                    <span class="inline-flex items-center gap-1.5 px-2 py-1 bg-[#fff7e8] text-[#9a6c28] rounded-[3px] text-[9px]">
                        <span class="w-[5px] h-[5px] rounded-full bg-[#d8a348]"></span>
                        No Show
                    </span>

                @endif

            </div>


            <div class="flex flex-wrap items-center gap-2 mt-1 text-[10px] text-[#929ba4]">

                <span>
                    {{ $property->name }}
                </span>

                <span>•</span>

                <span>
                    Created
                    {{ $reservation->booked_at ? $reservation->booked_at->format('d/m/Y H:i') : '-' }}
                </span>

                <span>•</span>

                <span>
                    {{ ucwords(str_replace('_', ' ', $reservation->source)) }}
                </span>

            </div>

        </div>


        <div class="flex flex-wrap gap-2">

            <a
                href="{{ route('pms.reservations.index') }}"
                class="h-[34px] px-4 inline-flex items-center justify-center border border-[#dce1e6] bg-white rounded-[3px] text-[10px] text-[#59636e]"
            >
                ← Reservations
            </a>


            <a
                href="{{ route('pms.front-desk.index') }}"
                class="h-[34px] px-4 inline-flex items-center justify-center border border-[#dce1e6] bg-white rounded-[3px] text-[10px] text-[#59636e]"
            >
                Front Desk
            </a>


            @if($reservation->status === 'checked_in' && $reservationRoom?->room)

                <a
                    href="{{ route('pms.room-moves.create', $reservation) }}"
                    class="h-[34px] px-4 inline-flex items-center justify-center border border-[#cfe1fa] bg-[#f7faff] text-[#1677ff] rounded-[3px] text-[10px]"
                >
                    Room Move
                </a>

            @endif


            @if(in_array($reservation->status, ['pending', 'confirmed'], true))

                <details class="relative">

                    <summary
                        class="list-none cursor-pointer h-[34px] px-4 inline-flex items-center justify-center border border-[#efcaca] bg-white text-[#b64646] rounded-[3px] text-[10px]"
                    >
                        No-show
                    </summary>

                    <div class="absolute right-0 z-30 mt-2 w-[320px] bg-white border border-[#e2e6ea] shadow-lg rounded-[3px] p-4">

                        <div class="text-[11px] font-medium text-[#36414c]">
                            Mark as No-show
                        </div>

                        <div class="text-[9px] text-[#929ba4] mt-1">
                            Đánh dấu khách không đến nhận phòng
                        </div>

                        <form
                            method="POST"
                            action="{{ route('pms.reservations.no-show', $reservation) }}"
                            class="mt-4 space-y-3"
                            onsubmit="return confirm('Mark reservation {{ $reservation->code }} as No-show?')"
                        >
                            @csrf

                            <div>

                                <label class="block text-[9px] uppercase text-[#7f8993] mb-1.5">
                                    Reason
                                </label>

                                <div class="text-[8px] text-[#a0a8b0] mb-2">
                                    Lý do hoặc ghi chú khi khách không đến
                                </div>

                                <input
                                    type="text"
                                    name="reason"
                                    maxlength="255"
                                    value="{{ old('reason') }}"
                                    placeholder="Example: Guest did not arrive"
                                    class="w-full h-[36px] px-3 border border-[#dce1e6] bg-white rounded-[3px] text-[10px] outline-none focus:border-[#1677ff]"
                                >

                            </div>

                            <div class="p-3 bg-[#fff7e8] border border-[#f0dfba] rounded-[3px]">

                                <div class="text-[9px] text-[#9a6c28] leading-5">
                                    Inventory sẽ được trả lại, Physical Room sẽ được bỏ gán và Availability sẽ được đồng bộ lại Channex. Payment hiện tại không tự động thay đổi.
                                </div>

                            </div>

                            <button
                                type="submit"
                                class="w-full h-[36px] bg-[#b64646] text-white rounded-[3px] text-[10px] font-medium"
                            >
                                Confirm No-show
                            </button>

                            <div class="text-[8px] text-[#929ba4] text-center">
                                Xác nhận khách không đến
                            </div>

                        </form>

                    </div>

                </details>

            @endif


            @if(!in_array($reservation->status, ['checked_out', 'cancelled', 'no_show'], true))

                <a
                    href="{{ route('pms.reservations.edit', $reservation) }}"
                    class="h-[34px] px-4 inline-flex items-center justify-center bg-[#1677ff] text-white rounded-[3px] text-[10px]"
                >
                    Edit Reservation
                </a>

            @endif

        </div>

    </div>


    {{-- ====================================================== --}}
    {{-- FLASH --}}
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

            @foreach($errors->all() as $error)

                <div class="text-[10px] text-[#b64646]">
                    {{ $error }}
                </div>

            @endforeach

        </div>

    @endif


    {{-- ====================================================== --}}
    {{-- GRID --}}
    {{-- ====================================================== --}}

    <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_380px] gap-4">

        {{-- ====================================================== --}}
        {{-- LEFT --}}
        {{-- ====================================================== --}}

        <div class="space-y-4">

            {{-- ====================================================== --}}
            {{-- STAY INFORMATION --}}
            {{-- ====================================================== --}}

            <div class="bg-white border border-[#e2e6ea] rounded-[3px] overflow-hidden">

                <div class="px-5 py-4 border-b border-[#edf0f2]">

                    <div class="text-[12px] font-medium text-[#36414c]">
                        Stay Information
                    </div>

                    <div class="text-[9px] text-[#929ba4] mt-1">
                        Booking dates, occupancy and booked room category
                    </div>

                </div>


                @if($reservationRoom)

                    <div class="grid grid-cols-2 lg:grid-cols-4 border-b border-[#edf0f2]">

                        <div class="p-5 border-r border-[#edf0f2]">

                            <div class="text-[9px] uppercase text-[#929ba4]">
                                Check-in
                            </div>

                            <div class="text-[15px] font-medium text-[#36414c] mt-1">
                                {{ $checkIn->format('d/m/Y') }}
                            </div>

                            <div class="text-[9px] text-[#929ba4] mt-1">
                                {{ $property->check_in_time ?? '14:00' }}
                            </div>

                        </div>


                        <div class="p-5 lg:border-r border-[#edf0f2]">

                            <div class="text-[9px] uppercase text-[#929ba4]">
                                Check-out
                            </div>

                            <div class="text-[15px] font-medium text-[#36414c] mt-1">
                                {{ $checkOut->format('d/m/Y') }}
                            </div>

                            <div class="text-[9px] text-[#929ba4] mt-1">
                                {{ $property->check_out_time ?? '12:00' }}
                            </div>

                        </div>


                        <div class="p-5 border-t lg:border-t-0 border-r border-[#edf0f2]">

                            <div class="text-[9px] uppercase text-[#929ba4]">
                                Nights
                            </div>

                            <div class="text-[15px] font-medium text-[#36414c] mt-1">
                                {{ $nights }}
                            </div>

                        </div>


                        <div class="p-5 border-t lg:border-t-0">

                            <div class="text-[9px] uppercase text-[#929ba4]">
                                Occupancy
                            </div>

                            <div class="text-[14px] font-medium text-[#36414c] mt-1">
                                {{ $reservationRoom->adults }} Adult(s)
                            </div>

                            <div class="text-[9px] text-[#929ba4] mt-1">
                                {{ $reservationRoom->children }} Child(ren)
                            </div>

                        </div>

                    </div>


                    <div class="p-5 grid grid-cols-1 md:grid-cols-3 gap-5">

                        <div>

                            <div class="text-[9px] uppercase text-[#929ba4]">
                                Room Type
                            </div>

                            <div class="text-[12px] font-medium text-[#36414c] mt-1">
                                {{ $reservationRoom->roomType?->name ?? '-' }}
                            </div>

                        </div>


                        <div>

                            <div class="text-[9px] uppercase text-[#929ba4]">
                                Rate Plan
                            </div>

                            <div class="text-[12px] font-medium text-[#36414c] mt-1">
                                {{ $reservationRoom->ratePlan?->name ?? 'No Rate Plan' }}
                            </div>

                        </div>


                        <div>

                            <div class="text-[9px] uppercase text-[#929ba4]">
                                Nightly Rate
                            </div>

                            <div class="text-[12px] font-medium text-[#36414c] mt-1">
                                {{ number_format((float) $reservationRoom->nightly_rate, 0, ',', '.') }} ₫
                            </div>

                        </div>

                    </div>

                @endif

            </div>


            {{-- ====================================================== --}}
            {{-- ROOM ASSIGNMENT --}}
            {{-- ====================================================== --}}

            <div class="bg-white border border-[#e2e6ea] rounded-[3px] overflow-hidden">

                <div class="px-5 py-4 border-b border-[#edf0f2]">

                    <div class="text-[12px] font-medium text-[#36414c]">
                        Physical Room Assignment
                    </div>

                    <div class="text-[9px] text-[#929ba4] mt-1">
                        Room used by Front Desk operations
                    </div>

                </div>


                <div class="p-5">

                    @if($reservationRoom?->room)

                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 p-4 bg-[#f7faff] border border-[#d9e8ff] rounded-[3px]">

                            <div>

                                <div class="text-[9px] uppercase text-[#929ba4]">
                                    Assigned Room
                                </div>

                                <div class="text-[18px] font-medium text-[#1677ff] mt-1">
                                    {{ $reservationRoom->room->room_number }}
                                </div>

                                <div class="text-[9px] text-[#7c8791] mt-1">
                                    {{ $reservationRoom->roomType?->name }}

                                    @if($reservationRoom->room->floor)
                                        · Floor {{ $reservationRoom->room->floor }}
                                    @endif
                                </div>

                            </div>


                            <div class="flex gap-2">

                                <span class="px-2 py-1 rounded-[3px] text-[9px] {{ $reservationRoom->room->status === 'occupied' ? 'bg-[#eef5ff] text-[#1677ff]' : 'bg-[#edf9f1] text-[#31845b]' }}">
                                    {{ ucwords(str_replace('_', ' ', $reservationRoom->room->status)) }}
                                </span>

                                <span class="px-2 py-1 rounded-[3px] text-[9px] bg-[#f3f5f7] text-[#59636e]">
                                    {{ ucfirst($reservationRoom->room->housekeeping_status) }}
                                </span>

                            </div>

                        </div>


                        @if(!in_array($reservation->status, ['checked_in', 'checked_out', 'cancelled', 'no_show'], true))

                            <div class="mt-4 pt-4 border-t border-[#edf0f2]">

                                <form
                                    method="POST"
                                    action="{{ route('pms.reservations.assign-room', $reservation) }}"
                                    class="grid grid-cols-1 sm:grid-cols-[1fr_auto_auto] gap-2"
                                >
                                    @csrf

                                    <select
                                        name="room_id"
                                        class="h-[36px] px-3 border border-[#dce1e6] bg-white rounded-[3px] text-[10px]"
                                    >
                                        <option value="">
                                            Change Physical Room
                                        </option>

                                        @foreach($availableRooms as $room)

                                            <option
                                                value="{{ $room->id }}"
                                                @selected((int) $reservationRoom->room_id === (int) $room->id)
                                            >
                                                Room {{ $room->room_number }}
                                                — {{ ucfirst($room->housekeeping_status) }}
                                            </option>

                                        @endforeach

                                    </select>


                                    <button
                                        type="submit"
                                        class="h-[36px] px-4 bg-[#1677ff] text-white rounded-[3px] text-[10px]"
                                    >
                                        Change
                                    </button>

                                </form>


                                <form
                                    method="POST"
                                    action="{{ route('pms.reservations.unassign-room', $reservation) }}"
                                    class="mt-2"
                                    onsubmit="return confirm('Unassign this physical room?')"
                                >
                                    @csrf
                                    @method('DELETE')

                                    <button
                                        type="submit"
                                        class="text-[9px] text-[#b64646]"
                                    >
                                        Unassign Room
                                    </button>

                                </form>

                            </div>

                        @endif


                    @elseif($reservationRoom && !in_array($reservation->status, ['cancelled', 'no_show', 'checked_out'], true))

                        <form
                            method="POST"
                            action="{{ route('pms.reservations.assign-room', $reservation) }}"
                            class="grid grid-cols-1 sm:grid-cols-[1fr_auto] gap-2"
                        >
                            @csrf

                            <select
                                name="room_id"
                                required
                                class="h-[38px] px-3 border border-[#dce1e6] bg-white rounded-[3px] text-[10px]"
                            >

                                <option value="">
                                    Select Physical Room
                                </option>

                                @foreach($availableRooms as $room)

                                    <option value="{{ $room->id }}">
                                        Room {{ $room->room_number }}
                                        @if($room->floor)
                                            — Floor {{ $room->floor }}
                                        @endif
                                        — {{ ucfirst($room->housekeeping_status) }}
                                    </option>

                                @endforeach

                            </select>


                            <button
                                type="submit"
                                class="h-[38px] px-5 bg-[#1677ff] text-white rounded-[3px] text-[10px]"
                            >
                                Assign Room
                            </button>

                        </form>

                    @else

                        <div class="text-[10px] text-[#929ba4]">
                            No physical room assignment available.
                        </div>

                    @endif

                </div>

            </div>


            {{-- ====================================================== --}}
            {{-- FOLIO --}}
            {{-- ====================================================== --}}

            <div class="bg-white border border-[#e2e6ea] rounded-[3px] overflow-hidden">

                <div class="px-5 py-4 border-b border-[#edf0f2] flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">

                    <div>

                        <div class="text-[12px] font-medium text-[#36414c]">
                            Folio
                        </div>

                        <div class="text-[9px] text-[#929ba4] mt-1">
                            Chi tiết tiền phòng, thuế, phí và các chi phí phát sinh của đặt phòng
                        </div>

                    </div>

                    @if(!in_array($reservation->status, ['cancelled', 'no_show'], true))

                        <details class="sm:text-right">

                            <summary class="cursor-pointer list-none inline-flex h-[32px] px-3 items-center justify-center bg-[#1677ff] text-white rounded-[3px] text-[9px]">
                                + Add Charge
                            </summary>

                            <div class="mt-3 sm:w-[430px] sm:text-left p-4 bg-[#f8fbff] border border-[#d9e8ff] rounded-[3px]">

                                <div class="mb-4">

                                    <div class="text-[11px] font-medium text-[#36414c]">
                                        Add Extra Charge
                                    </div>

                                    <div class="text-[9px] text-[#929ba4] mt-1">
                                        Thêm chi phí phát sinh vào hóa đơn của khách
                                    </div>

                                </div>

                                <form method="POST" action="{{ route('pms.folio-items.store', $reservation) }}" class="space-y-3">
                                    @csrf

                                    <div>
                                        <label class="block text-[9px] uppercase text-[#7f8993] mb-1.5">
                                            Category *
                                        </label>
                                        <div class="text-[8px] text-[#a0a8b0] mb-1.5">
                                            Loại chi phí phát sinh
                                        </div>

                                        <select name="category" required class="w-full h-[36px] px-3 border border-[#dce1e6] bg-white rounded-[3px] text-[10px] outline-none focus:border-[#1677ff]">
                                            <option value="">Select Category / Chọn loại chi phí</option>
                                            <option value="minibar" @selected(old('category') === 'minibar')>Minibar — Đồ ăn, thức uống minibar</option>
                                            <option value="laundry" @selected(old('category') === 'laundry')>Laundry — Giặt ủi</option>
                                            <option value="airport_transfer" @selected(old('category') === 'airport_transfer')>Airport Transfer — Đưa đón sân bay</option>
                                            <option value="extra_bed" @selected(old('category') === 'extra_bed')>Extra Bed — Giường phụ</option>
                                            <option value="late_checkout" @selected(old('category') === 'late_checkout')>Late Checkout — Trả phòng trễ</option>
                                            <option value="damage_fee" @selected(old('category') === 'damage_fee')>Damage Fee — Phí hư hỏng</option>
                                            <option value="other" @selected(old('category') === 'other')>Other — Chi phí khác</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-[9px] uppercase text-[#7f8993] mb-1.5">
                                            Description *
                                        </label>
                                        <div class="text-[8px] text-[#a0a8b0] mb-1.5">
                                            Mô tả khoản phí, ví dụ: Coca Cola, Laundry Service...
                                        </div>
                                        <input type="text" name="description" value="{{ old('description') }}" required placeholder="Example: Coca Cola 330ml" class="w-full h-[36px] px-3 border border-[#dce1e6] bg-white rounded-[3px] text-[10px] outline-none focus:border-[#1677ff]">
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">

                                        <div>
                                            <label class="block text-[9px] uppercase text-[#7f8993] mb-1.5">
                                                Quantity *
                                            </label>
                                            <div class="text-[8px] text-[#a0a8b0] mb-1.5">
                                                Số lượng
                                            </div>
                                            <input type="number" name="quantity" min="1" max="100" value="{{ old('quantity', 1) }}" required class="w-full h-[36px] px-3 border border-[#dce1e6] bg-white rounded-[3px] text-[10px] outline-none focus:border-[#1677ff]">
                                        </div>

                                        <div>
                                            <label class="block text-[9px] uppercase text-[#7f8993] mb-1.5">
                                                Unit Price *
                                            </label>
                                            <div class="text-[8px] text-[#a0a8b0] mb-1.5">
                                                Đơn giá (VND)
                                            </div>
                                            <input type="number" name="unit_price" min="0" step="1" value="{{ old('unit_price') }}" required placeholder="0" class="w-full h-[36px] px-3 border border-[#dce1e6] bg-white rounded-[3px] text-[10px] outline-none focus:border-[#1677ff]">
                                        </div>

                                    </div>

                                    <div>
                                        <label class="block text-[9px] uppercase text-[#7f8993] mb-1.5">
                                            Posted At
                                        </label>
                                        <div class="text-[8px] text-[#a0a8b0] mb-1.5">
                                            Thời điểm ghi nhận chi phí
                                        </div>
                                        <input type="datetime-local" name="posted_at" value="{{ old('posted_at', $defaultFolioPostedAt) }}" class="w-full h-[36px] px-3 border border-[#dce1e6] bg-white rounded-[3px] text-[10px] outline-none focus:border-[#1677ff]">
                                    </div>

                                    <div>
                                        <label class="block text-[9px] uppercase text-[#7f8993] mb-1.5">
                                            Notes
                                        </label>
                                        <div class="text-[8px] text-[#a0a8b0] mb-1.5">
                                            Ghi chú nội bộ, không bắt buộc
                                        </div>
                                        <textarea name="notes" rows="2" class="w-full px-3 py-2 border border-[#dce1e6] bg-white rounded-[3px] text-[10px] resize-none outline-none focus:border-[#1677ff]">{{ old('notes') }}</textarea>
                                    </div>

                                    <button type="submit" class="w-full h-[36px] bg-[#1677ff] text-white rounded-[3px] text-[10px] font-medium">
                                        Add Charge / Thêm chi phí
                                    </button>

                                </form>

                            </div>

                        </details>

                    @endif

                </div>


                {{-- BASE CHARGES --}}
                <div class="p-5 border-b border-[#edf0f2]">

                    <div class="flex items-center justify-between mb-4">

                        <div>
                            <div class="text-[10px] font-medium text-[#36414c]">
                                Base Charges
                            </div>
                            <div class="text-[8px] text-[#a0a8b0] mt-0.5">
                                Các khoản phí cơ bản của đặt phòng
                            </div>
                        </div>

                        <div class="text-[11px] font-medium text-[#36414c]">
                            {{ number_format($baseReservationTotal, 0, ',', '.') }} ₫
                        </div>

                    </div>

                    <div class="space-y-3">

                        <div class="flex items-start justify-between gap-4">

                            <div>
                                <div class="text-[10px] text-[#59636e]">
                                    Room Charges
                                </div>
                                <div class="text-[8px] text-[#a0a8b0] mt-0.5">
                                    Tiền phòng
                                </div>

                                @if($reservationRoom)
                                    <div class="text-[9px] text-[#929ba4] mt-1">
                                        {{ $nights }} night(s) × {{ number_format((float) $reservationRoom->nightly_rate, 0, ',', '.') }} ₫
                                    </div>
                                @endif
                            </div>

                            <div class="text-[10px] font-medium text-[#36414c]">
                                {{ number_format((float) $reservation->subtotal, 0, ',', '.') }} ₫
                            </div>

                        </div>

                        <div class="flex items-start justify-between gap-4 pt-3 border-t border-[#edf0f2]">
                            <div>
                                <div class="text-[10px] text-[#59636e]">Tax</div>
                                <div class="text-[8px] text-[#a0a8b0] mt-0.5">Thuế</div>
                            </div>
                            <div class="text-[10px] text-[#36414c]">
                                {{ number_format((float) $reservation->tax_amount, 0, ',', '.') }} ₫
                            </div>
                        </div>

                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <div class="text-[10px] text-[#59636e]">Fee</div>
                                <div class="text-[8px] text-[#a0a8b0] mt-0.5">Phí dịch vụ hoặc phụ phí cơ bản</div>
                            </div>
                            <div class="text-[10px] text-[#36414c]">
                                {{ number_format((float) $reservation->fee_amount, 0, ',', '.') }} ₫
                            </div>
                        </div>

                    </div>

                </div>


                {{-- EXTRA CHARGES --}}
                <div>

                    <div class="px-5 py-4 bg-[#fafbfc] border-b border-[#edf0f2] flex items-center justify-between gap-4">

                        <div>
                            <div class="text-[10px] font-medium text-[#36414c]">
                                Extra Charges
                            </div>
                            <div class="text-[8px] text-[#a0a8b0] mt-0.5">
                                Các chi phí phát sinh trong thời gian lưu trú
                            </div>
                        </div>

                        <div class="text-right">
                            <div class="text-[11px] font-medium text-[#d17e15]">
                                {{ number_format($extraChargesTotal, 0, ',', '.') }} ₫
                            </div>
                            <div class="text-[8px] text-[#a0a8b0] mt-0.5">
                                {{ $activeFolioItems->count() }} active item(s)
                            </div>
                        </div>

                    </div>

                    @if($reservation->folioItems->isEmpty())

                        <div class="py-10 px-5 text-center">
                            <div class="text-[10px] text-[#7c8791]">
                                No extra charges recorded.
                            </div>
                            <div class="text-[9px] text-[#a0a8b0] mt-1">
                                Chưa có chi phí phát sinh nào được ghi nhận.
                            </div>
                        </div>

                    @else

                        <div class="divide-y divide-[#edf0f2]">

                            @foreach($reservation->folioItems as $item)

                                @php
                                    $categoryLabel = match ($item->category) {
                                        'minibar' => 'Minibar',
                                        'laundry' => 'Laundry',
                                        'airport_transfer' => 'Airport Transfer',
                                        'extra_bed' => 'Extra Bed',
                                        'late_checkout' => 'Late Checkout',
                                        'damage_fee' => 'Damage Fee',
                                        default => 'Other',
                                    };

                                    $categoryVi = match ($item->category) {
                                        'minibar' => 'Minibar',
                                        'laundry' => 'Giặt ủi',
                                        'airport_transfer' => 'Đưa đón sân bay',
                                        'extra_bed' => 'Giường phụ',
                                        'late_checkout' => 'Trả phòng trễ',
                                        'damage_fee' => 'Phí hư hỏng',
                                        default => 'Chi phí khác',
                                    };
                                @endphp

                                <div class="p-5 {{ $item->status === 'voided' ? 'bg-[#fafafa]' : '' }}">

                                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">

                                        <div class="min-w-0">

                                            <div class="flex flex-wrap items-center gap-2">

                                                <span class="text-[10px] font-medium {{ $item->status === 'voided' ? 'text-[#929ba4] line-through' : 'text-[#1677ff]' }}">
                                                    {{ $item->code }}
                                                </span>

                                                @if($item->status === 'active')
                                                    <span class="px-2 py-1 bg-[#edf9f1] text-[#31845b] rounded-[3px] text-[8px]">
                                                        Active / Đang tính phí
                                                    </span>
                                                @else
                                                    <span class="px-2 py-1 bg-[#fff0f0] text-[#b64646] rounded-[3px] text-[8px]">
                                                        Voided / Đã hủy
                                                    </span>
                                                @endif

                                            </div>

                                            <div class="text-[11px] font-medium {{ $item->status === 'voided' ? 'text-[#929ba4] line-through' : 'text-[#36414c]' }} mt-2">
                                                {{ $item->description }}
                                            </div>

                                            <div class="text-[9px] text-[#929ba4] mt-1">
                                                {{ $categoryLabel }} · {{ $categoryVi }}
                                            </div>

                                            <div class="text-[9px] text-[#929ba4] mt-1">
                                                {{ $item->quantity }} × {{ number_format((float) $item->unit_price, 0, ',', '.') }} ₫
                                                @if($item->posted_at)
                                                    · {{ $item->posted_at->format('d/m/Y H:i') }}
                                                @endif
                                            </div>

                                            @if($item->notes)
                                                <div class="text-[9px] text-[#7c8791] mt-2">
                                                    Note / Ghi chú: {{ $item->notes }}
                                                </div>
                                            @endif

                                        </div>

                                        <div class="sm:text-right shrink-0">

                                            <div class="text-[13px] font-medium {{ $item->status === 'voided' ? 'text-[#929ba4] line-through' : 'text-[#36414c]' }}">
                                                {{ number_format((float) $item->total_amount, 0, ',', '.') }} ₫
                                            </div>

                                            @if($item->status === 'active')
                                                <form method="POST" action="{{ route('pms.folio-items.void', $item) }}" class="mt-3" onsubmit="return confirm('Void charge {{ $item->code }}? / Hủy khoản phí {{ $item->code }}?')">
                                                    @csrf
                                                    @method('PATCH')

                                                    <button type="submit" class="h-[28px] px-3 border border-[#efcaca] bg-white text-[#b64646] rounded-[3px] text-[9px]">
                                                        Void / Hủy phí
                                                    </button>
                                                </form>
                                            @endif

                                        </div>

                                    </div>

                                </div>

                            @endforeach

                        </div>

                    @endif

                </div>


                {{-- FOLIO TOTAL --}}
                <div class="p-5 bg-[#fafcff] border-t border-[#dce7f5]">

                    <div class="space-y-3">

                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <div class="text-[10px] text-[#59636e]">Base Total</div>
                                <div class="text-[8px] text-[#a0a8b0] mt-0.5">Tổng tiền phòng + thuế + phí cơ bản</div>
                            </div>
                            <div class="text-[10px] text-[#36414c]">
                                {{ number_format($baseReservationTotal, 0, ',', '.') }} ₫
                            </div>
                        </div>

                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <div class="text-[10px] text-[#59636e]">Extra Charges</div>
                                <div class="text-[8px] text-[#a0a8b0] mt-0.5">Tổng chi phí phát sinh đang có hiệu lực</div>
                            </div>
                            <div class="text-[10px] text-[#d17e15]">
                                +{{ number_format($extraChargesTotal, 0, ',', '.') }} ₫
                            </div>
                        </div>

                        <div class="pt-3 border-t border-[#dce7f5] flex items-end justify-between gap-4">
                            <div>
                                <div class="text-[12px] font-medium text-[#303942]">Grand Total</div>
                                <div class="text-[8px] text-[#a0a8b0] mt-0.5">Tổng giá trị hiện tại của đặt phòng</div>
                            </div>
                            <div class="text-[17px] font-semibold text-[#303942]">
                                {{ number_format((float) $reservation->total_amount, 0, ',', '.') }} ₫
                            </div>
                        </div>

                    </div>

                </div>

            </div>


            {{-- ====================================================== --}}
            {{-- PAYMENT HISTORY --}}
            {{-- ====================================================== --}}

            <div class="bg-white border border-[#e2e6ea] rounded-[3px] overflow-hidden">

                <div class="px-5 py-4 border-b border-[#edf0f2]">

                    <div class="text-[12px] font-medium text-[#36414c]">
                        Payment History
                    </div>

                    <div class="text-[9px] text-[#929ba4] mt-1">
                        Lịch sử các khoản tiền đã thu từ khách hoặc đơn vị thanh toán
                    </div>

                </div>


                @if($reservation->payments->isEmpty())

                    <div class="py-12 text-center text-[10px] text-[#929ba4]">
                        No payment recorded.
                    </div>

                @else

                    <div class="divide-y divide-[#edf0f2]">

                        @foreach($reservation->payments as $payment)

                            @php
                                $paymentRefunded = (float) $payment
                                    ->refunds
                                    ->where('status', 'completed')
                                    ->sum('amount');

                                $refundableAmount = max(
                                    0,
                                    (float) $payment->amount
                                    -
                                    $paymentRefunded
                                );

                                $hasCompletedRefund = $paymentRefunded > 0;
                            @endphp


                            <div class="p-5 {{ $payment->status === 'voided' ? 'bg-[#fafafa]' : '' }}">

                                <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">

                                    <div>

                                        <div class="flex flex-wrap items-center gap-2">

                                            <span class="text-[11px] font-medium {{ $payment->status === 'voided' ? 'text-[#929ba4] line-through' : 'text-[#1677ff]' }}">
                                                {{ $payment->code }}
                                            </span>


                                            @if($payment->status === 'completed')

                                                <span class="px-2 py-1 bg-[#edf9f1] text-[#31845b] rounded-[3px] text-[8px]">
                                                    Completed
                                                </span>

                                            @elseif($payment->status === 'voided')

                                                <span class="px-2 py-1 bg-[#fff0f0] text-[#b64646] rounded-[3px] text-[8px]">
                                                    Voided
                                                </span>

                                            @endif


                                            @if($hasCompletedRefund)

                                                <span class="px-2 py-1 bg-[#fff7e8] text-[#9a6c28] rounded-[3px] text-[8px]">
                                                    Refunded {{ number_format($paymentRefunded, 0, ',', '.') }} ₫
                                                </span>

                                            @endif

                                        </div>


                                        <div class="text-[10px] text-[#36414c] mt-2">
                                            {{ ucwords(str_replace('_', ' ', $payment->payment_method)) }}
                                        </div>


                                        <div class="text-[9px] text-[#929ba4] mt-1">

                                            {{ $payment->provider ?: 'No provider' }}

                                            @if($payment->transaction_reference)
                                                · {{ $payment->transaction_reference }}
                                            @endif

                                        </div>


                                        <div class="text-[9px] text-[#929ba4] mt-1">
                                            {{ $payment->paid_at ? $payment->paid_at->format('d/m/Y H:i') : '-' }}
                                        </div>

                                    </div>


                                    <div class="lg:text-right">

                                        <div class="text-[15px] font-medium {{ $payment->status === 'voided' ? 'text-[#929ba4] line-through' : 'text-[#36414c]' }}">
                                            {{ number_format((float) $payment->amount, 0, ',', '.') }} ₫
                                        </div>


                                        @if($payment->status === 'completed')

                                            <div class="text-[9px] text-[#929ba4] mt-1">
                                                Refundable:
                                                {{ number_format($refundableAmount, 0, ',', '.') }} ₫
                                            </div>

                                        @endif

                                    </div>

                                </div>


                                {{-- ACTIONS --}}
                                @if($payment->status === 'completed')

                                    <div class="mt-4 pt-4 border-t border-[#edf0f2] flex flex-wrap gap-2">

                                        @if(!$hasCompletedRefund)

                                            <form
                                                method="POST"
                                                action="{{ route('pms.payments.void', $payment) }}"
                                                onsubmit="return confirm('Void payment {{ $payment->code }}?')"
                                            >
                                                @csrf
                                                @method('PATCH')

                                                <button
                                                    type="submit"
                                                    class="h-[30px] px-3 border border-[#efcaca] text-[#b64646] bg-white rounded-[3px] text-[9px]"
                                                >
                                                    Void Payment
                                                </button>

                                            </form>

                                        @endif


                                        @if($refundableAmount > 0)

                                            <details class="w-full mt-2">

                                                <summary class="cursor-pointer inline-flex h-[30px] px-3 items-center bg-[#fff7e8] border border-[#f0dfba] text-[#9a6c28] rounded-[3px] text-[9px]">
                                                    Refund Payment
                                                </summary>


                                                <div class="mt-3 p-4 bg-[#fffdf8] border border-[#f0dfba] rounded-[3px]">

                                                    <form
                                                        method="POST"
                                                        action="{{ route('pms.payments.refund', $payment) }}"
                                                        class="space-y-3"
                                                    >

                                                        @csrf


                                                        <div>

                                                            <label class="block text-[9px] uppercase text-[#7f8993] mb-1.5">
                                                                Refund Amount *
                                                            </label>

                                                            <input
                                                                type="number"
                                                                name="amount"
                                                                min="1"
                                                                max="{{ number_format($refundableAmount, 0, '.', '') }}"
                                                                value="{{ number_format($refundableAmount, 0, '.', '') }}"
                                                                required
                                                                class="w-full h-[36px] px-3 border border-[#dce1e6] bg-white rounded-[3px] text-[10px]"
                                                            >

                                                            <div class="text-[9px] text-[#929ba4] mt-1">
                                                                Maximum:
                                                                {{ number_format($refundableAmount, 0, ',', '.') }} ₫
                                                            </div>

                                                        </div>


                                                        <div>

                                                            <label class="block text-[9px] uppercase text-[#7f8993] mb-1.5">
                                                                Refund Method
                                                            </label>

                                                            <select
                                                                name="refund_method"
                                                                class="w-full h-[36px] px-3 border border-[#dce1e6] bg-white rounded-[3px] text-[10px]"
                                                            >

                                                                <option value="">
                                                                    Same as original payment
                                                                </option>

                                                                <option value="cash">
                                                                    Cash
                                                                </option>

                                                                <option value="bank_transfer">
                                                                    Bank Transfer
                                                                </option>

                                                                <option value="card">
                                                                    Card
                                                                </option>

                                                                <option value="payment_gateway">
                                                                    Payment Gateway
                                                                </option>

                                                                <option value="ota">
                                                                    OTA
                                                                </option>

                                                                <option value="other">
                                                                    Other
                                                                </option>

                                                            </select>

                                                        </div>


                                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">

                                                            <div>

                                                                <label class="block text-[9px] uppercase text-[#7f8993] mb-1.5">
                                                                    Provider
                                                                </label>

                                                                <input
                                                                    type="text"
                                                                    name="provider"
                                                                    placeholder="Vietcombank, VNPay..."
                                                                    class="w-full h-[36px] px-3 border border-[#dce1e6] bg-white rounded-[3px] text-[10px]"
                                                                >

                                                            </div>


                                                            <div>

                                                                <label class="block text-[9px] uppercase text-[#7f8993] mb-1.5">
                                                                    Refund Reference
                                                                </label>

                                                                <input
                                                                    type="text"
                                                                    name="transaction_reference"
                                                                    placeholder="Refund transaction ID"
                                                                    class="w-full h-[36px] px-3 border border-[#dce1e6] bg-white rounded-[3px] text-[10px]"
                                                                >

                                                            </div>

                                                        </div>


                                                        <div>

                                                            <label class="block text-[9px] uppercase text-[#7f8993] mb-1.5">
                                                                Reason
                                                            </label>

                                                            <input
                                                                type="text"
                                                                name="reason"
                                                                placeholder="Guest cancellation, overcharge..."
                                                                class="w-full h-[36px] px-3 border border-[#dce1e6] bg-white rounded-[3px] text-[10px]"
                                                            >

                                                        </div>


                                                        <div>

                                                            <label class="block text-[9px] uppercase text-[#7f8993] mb-1.5">
                                                                Refunded At
                                                            </label>

                                                            <input
                                                                type="datetime-local"
                                                                name="refunded_at"
                                                                value="{{ $defaultRefundedAt }}"
                                                                class="w-full h-[36px] px-3 border border-[#dce1e6] bg-white rounded-[3px] text-[10px]"
                                                            >

                                                        </div>


                                                        <div>

                                                            <label class="block text-[9px] uppercase text-[#7f8993] mb-1.5">
                                                                Notes
                                                            </label>

                                                            <textarea
                                                                name="notes"
                                                                rows="2"
                                                                class="w-full px-3 py-2 border border-[#dce1e6] bg-white rounded-[3px] text-[10px] resize-none"
                                                            ></textarea>

                                                        </div>


                                                        <button
                                                            type="submit"
                                                            class="w-full h-[36px] bg-[#d58a22] text-white rounded-[3px] text-[10px]"
                                                            onclick="return confirm('Confirm refund for {{ $payment->code }}?')"
                                                        >
                                                            Confirm Refund
                                                        </button>

                                                    </form>

                                                </div>

                                            </details>

                                        @endif

                                    </div>

                                @endif

                            </div>

                        @endforeach

                    </div>

                @endif

            </div>


            {{-- ====================================================== --}}
            {{-- REFUND HISTORY --}}
            {{-- ====================================================== --}}

            <div class="bg-white border border-[#e2e6ea] rounded-[3px] overflow-hidden">

                <div class="px-5 py-4 border-b border-[#edf0f2]">

                    <div class="text-[12px] font-medium text-[#36414c]">
                        Refund History
                    </div>

                    <div class="text-[9px] text-[#929ba4] mt-1">
                        Lịch sử các khoản tiền đã hoàn lại cho khách
                    </div>

                </div>


                @if($reservation->refunds->isEmpty())

                    <div class="py-10 text-center text-[10px] text-[#929ba4]">
                        No refund recorded.
                    </div>

                @else

                    <div class="divide-y divide-[#edf0f2]">

                        @foreach($reservation->refunds as $refund)

                            <div class="p-5 {{ $refund->status === 'voided' ? 'bg-[#fafafa]' : '' }}">

                                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">

                                    <div>

                                        <div class="flex flex-wrap items-center gap-2">

                                            <span class="text-[11px] font-medium {{ $refund->status === 'voided' ? 'text-[#929ba4] line-through' : 'text-[#d17e15]' }}">
                                                {{ $refund->code }}
                                            </span>


                                            @if($refund->status === 'completed')

                                                <span class="px-2 py-1 bg-[#fff7e8] text-[#9a6c28] rounded-[3px] text-[8px]">
                                                    Completed
                                                </span>

                                            @else

                                                <span class="px-2 py-1 bg-[#fff0f0] text-[#b64646] rounded-[3px] text-[8px]">
                                                    Voided
                                                </span>

                                            @endif

                                        </div>


                                        <div class="text-[10px] text-[#36414c] mt-2">

                                            Refund of

                                            <span class="text-[#1677ff]">
                                                {{ $refund->payment?->code ?? '-' }}
                                            </span>

                                        </div>


                                        <div class="text-[9px] text-[#929ba4] mt-1">

                                            {{ $refund->refund_method ? ucwords(str_replace('_', ' ', $refund->refund_method)) : '-' }}

                                            @if($refund->provider)
                                                · {{ $refund->provider }}
                                            @endif

                                        </div>


                                        @if($refund->reason)

                                            <div class="text-[9px] text-[#7c8791] mt-2">
                                                Reason: {{ $refund->reason }}
                                            </div>

                                        @endif


                                        <div class="text-[9px] text-[#929ba4] mt-1">
                                            {{ $refund->refunded_at ? $refund->refunded_at->format('d/m/Y H:i') : '-' }}
                                        </div>

                                    </div>


                                    <div class="sm:text-right">

                                        <div class="text-[15px] font-medium {{ $refund->status === 'voided' ? 'text-[#929ba4] line-through' : 'text-[#b46d15]' }}">
                                            -{{ number_format((float) $refund->amount, 0, ',', '.') }} ₫
                                        </div>


                                        @if($refund->status === 'completed')

                                            <form
                                                method="POST"
                                                action="{{ route('pms.refunds.void', $refund) }}"
                                                class="mt-3"
                                                onsubmit="return confirm('Void refund {{ $refund->code }}?')"
                                            >
                                                @csrf
                                                @method('PATCH')

                                                <button
                                                    type="submit"
                                                    class="h-[28px] px-3 border border-[#efcaca] bg-white text-[#b64646] rounded-[3px] text-[9px]"
                                                >
                                                    Void Refund
                                                </button>

                                            </form>

                                        @endif

                                    </div>

                                </div>

                            </div>

                        @endforeach

                    </div>

                @endif

            </div>


            {{-- ====================================================== --}}
            {{-- BOOKING INFORMATION --}}
            {{-- ====================================================== --}}

            <div class="bg-white border border-[#e2e6ea] rounded-[3px] overflow-hidden">

                <div class="px-5 py-4 border-b border-[#edf0f2]">

                    <div class="text-[12px] font-medium text-[#36414c]">
                        Booking Information
                    </div>

                    <div class="text-[9px] text-[#929ba4] mt-1">
                        Nguồn đặt phòng và các mã tham chiếu từ hệ thống bên ngoài
                    </div>

                </div>


                <div class="p-5 grid grid-cols-2 lg:grid-cols-4 gap-5">

                    <div>

                        <div class="text-[9px] uppercase text-[#929ba4]">
                            Source
                        </div>

                        <div class="text-[10px] mt-1">
                            {{ ucwords(str_replace('_', ' ', $reservation->source)) }}
                        </div>

                    </div>


                    <div>

                        <div class="text-[9px] uppercase text-[#929ba4]">
                            Channel
                        </div>

                        <div class="text-[10px] mt-1">
                            {{ $reservation->channel ?: '-' }}
                        </div>

                    </div>


                    <div>

                        <div class="text-[9px] uppercase text-[#929ba4]">
                            External ID
                        </div>

                        <div class="text-[10px] mt-1 break-all">
                            {{ $reservation->external_reservation_id ?: '-' }}
                        </div>

                    </div>


                    <div>

                        <div class="text-[9px] uppercase text-[#929ba4]">
                            Channex ID
                        </div>

                        <div class="text-[10px] mt-1 break-all">
                            {{ $reservation->channex_booking_id ?: '-' }}
                        </div>

                    </div>


                    @if($reservation->status === 'no_show')

                        <div>

                            <div class="text-[9px] uppercase text-[#929ba4]">
                                No-show At
                            </div>

                            <div class="text-[10px] mt-1">
                                {{ $reservation->no_show_at ? $reservation->no_show_at->format('d/m/Y H:i') : '-' }}
                            </div>

                            <div class="text-[8px] text-[#a0a8b0] mt-0.5">
                                Thời điểm xác nhận khách không đến
                            </div>

                        </div>


                        <div>

                            <div class="text-[9px] uppercase text-[#929ba4]">
                                No-show Reason
                            </div>

                            <div class="text-[10px] mt-1">
                                {{ $reservation->no_show_reason ?: '-' }}
                            </div>

                            <div class="text-[8px] text-[#a0a8b0] mt-0.5">
                                Lý do khách không nhận phòng
                            </div>

                        </div>

                    @endif

                </div>

            </div>

        </div>


        {{-- ====================================================== --}}
        {{-- RIGHT --}}
        {{-- ====================================================== --}}

        <div class="space-y-4">

            {{-- ====================================================== --}}
            {{-- PAYMENT SUMMARY --}}
            {{-- ====================================================== --}}


            <div class="bg-white border border-[#e2e6ea] rounded-[3px] overflow-hidden">

                <div class="px-5 py-4 border-b border-[#edf0f2] flex items-start justify-between gap-4">

                    <div>

                        <div class="text-[12px] font-medium text-[#36414c]">
                            Payment Summary
                        </div>

                        <div class="text-[9px] text-[#929ba4] mt-1">
                            Tổng quan tình trạng thanh toán của đặt phòng
                        </div>

                    </div>


                    @if($paymentStatus === 'paid')

                        <div class="text-right">

                            <span class="inline-flex px-2 py-1 bg-[#edf9f1] text-[#31845b] rounded-[3px] text-[9px]">
                                Paid
                            </span>

                            <div class="text-[8px] text-[#929ba4] mt-1">
                                Đã thanh toán đủ
                            </div>

                        </div>

                    @elseif($paymentStatus === 'partial')

                        <div class="text-right">

                            <span class="inline-flex px-2 py-1 bg-[#fff7e8] text-[#9a6c28] rounded-[3px] text-[9px]">
                                Partial
                            </span>

                            <div class="text-[8px] text-[#929ba4] mt-1">
                                Thanh toán một phần
                            </div>

                        </div>

                    @elseif($paymentStatus === 'refunded')

                        <div class="text-right">

                            <span class="inline-flex px-2 py-1 bg-[#f1f3f5] text-[#68737e] rounded-[3px] text-[9px]">
                                Refunded
                            </span>

                            <div class="text-[8px] text-[#929ba4] mt-1">
                                Đã hoàn toàn bộ tiền thực nhận
                            </div>

                        </div>

                    @else

                        <div class="text-right">

                            <span class="inline-flex px-2 py-1 bg-[#fff0f0] text-[#b64646] rounded-[3px] text-[9px]">
                                Unpaid
                            </span>

                            <div class="text-[8px] text-[#929ba4] mt-1">
                                Chưa thanh toán
                            </div>

                        </div>

                    @endif

                </div>


                <div class="p-5 space-y-4">

                    {{-- TOTAL --}}
                    <div class="flex items-start justify-between gap-4">

                        <div>

                            <div class="text-[10px] text-[#59636e]">
                                Total
                            </div>

                            <div class="text-[8px] text-[#a0a8b0] mt-0.5">
                                Tổng giá trị đặt phòng
                            </div>

                        </div>

                        <div class="text-[11px] font-medium text-[#303942]">
                            {{ number_format((float) $reservation->total_amount, 0, ',', '.') }} ₫
                        </div>

                    </div>


                    {{-- GROSS PAID --}}
                    <div class="flex items-start justify-between gap-4">

                        <div>

                            <div class="text-[10px] text-[#59636e]">
                                Gross Paid
                            </div>

                            <div class="text-[8px] text-[#a0a8b0] mt-0.5">
                                Tổng tiền đã thu từ khách
                            </div>

                        </div>

                        <div class="text-[11px] font-medium text-[#31845b]">
                            {{ number_format($displayGrossPaid, 0, ',', '.') }} ₫
                        </div>

                    </div>


                    {{-- REFUNDED --}}
                    <div class="flex items-start justify-between gap-4">

                        <div>

                            <div class="text-[10px] text-[#59636e]">
                                Refunded
                            </div>

                            <div class="text-[8px] text-[#a0a8b0] mt-0.5">
                                Tổng tiền đã hoàn lại cho khách
                            </div>

                        </div>

                        <div class="text-[11px] font-medium text-[#d17e15]">
                            -{{ number_format($displayRefundedAmount, 0, ',', '.') }} ₫
                        </div>

                    </div>


                    {{-- NET PAID --}}
                    <div class="pt-4 border-t border-[#edf0f2] flex items-start justify-between gap-4">

                        <div>

                            <div class="text-[11px] font-medium text-[#303942]">
                                Net Paid
                            </div>

                            <div class="text-[8px] text-[#a0a8b0] mt-0.5">
                                Số tiền thực nhận sau khi trừ hoàn tiền
                            </div>

                        </div>

                        <div class="text-[12px] font-semibold text-[#31845b]">
                            {{ number_format($paidAmount, 0, ',', '.') }} ₫
                        </div>

                    </div>


                    {{-- BALANCE --}}
                    <div class="pt-4 border-t border-[#edf0f2] flex items-end justify-between gap-4">

                        <div>

                            <div class="text-[11px] font-medium text-[#303942]">
                                Balance
                            </div>

                            <div class="text-[8px] text-[#a0a8b0] mt-0.5">
                                Số tiền khách còn phải thanh toán
                            </div>

                        </div>

                        <div class="text-[19px] font-semibold {{ $outstandingBalance > 0 ? 'text-[#b64646]' : 'text-[#31845b]' }}">
                            {{ number_format($outstandingBalance, 0, ',', '.') }} ₫
                        </div>

                    </div>

                </div>

            </div>


            {{-- ====================================================== --}}
            {{-- RECORD PAYMENT --}}
            {{-- ====================================================== --}}

            @if($outstandingBalance > 0 && !in_array($reservation->status, ['cancelled', 'no_show'], true))

                <div class="bg-white border border-[#d8e5f7] rounded-[3px] overflow-hidden">

                    <div class="px-5 py-4 border-b border-[#edf0f2]">

                        <div class="text-[12px] font-medium text-[#36414c]">
                            Record Payment
                        </div>

                        <div class="text-[9px] text-[#929ba4] mt-1">
                            Ghi nhận khoản tiền khách vừa thanh toán
                        </div>

                    </div>


                    <form
                        method="POST"
                        action="{{ route('pms.payments.store', $reservation) }}"
                    >
                        @csrf


                        <div class="p-5 space-y-4">

                            <div>

                                <label class="block text-[9px] uppercase text-[#7f8993] mb-1.5">
                                    Amount *
                                </label>

                                <input
                                    type="number"
                                    name="amount"
                                    min="1"
                                    max="{{ number_format($outstandingBalance, 0, '.', '') }}"
                                    value="{{ number_format($outstandingBalance, 0, '.', '') }}"
                                    required
                                    class="w-full h-[38px] px-3 border border-[#dce1e6] rounded-[3px] text-[11px]"
                                >

                                <div class="text-[9px] text-[#929ba4] mt-1">
                                    Outstanding:
                                    {{ number_format($outstandingBalance, 0, ',', '.') }} ₫
                                </div>

                            </div>


                            <div>

                                <label class="block text-[9px] uppercase text-[#7f8993] mb-1.5">
                                    Payment Method *
                                </label>

                                <select
                                    name="payment_method"
                                    required
                                    class="w-full h-[36px] px-3 border border-[#dce1e6] bg-white rounded-[3px] text-[10px]"
                                >

                                    <option value="">
                                        Select Method
                                    </option>

                                    <option value="cash">
                                        Cash
                                    </option>

                                    <option value="bank_transfer">
                                        Bank Transfer
                                    </option>

                                    <option value="card">
                                        Card
                                    </option>

                                    <option value="payment_gateway">
                                        Payment Gateway
                                    </option>

                                    <option value="ota_collect">
                                        OTA Collect
                                    </option>

                                    <option value="other">
                                        Other
                                    </option>

                                </select>

                            </div>


                            <div>

                                <label class="block text-[9px] uppercase text-[#7f8993] mb-1.5">
                                    Provider
                                </label>

                                <input
                                    type="text"
                                    name="provider"
                                    placeholder="VNPay, Vietcombank..."
                                    class="w-full h-[36px] px-3 border border-[#dce1e6] rounded-[3px] text-[10px]"
                                >

                            </div>


                            <div>

                                <label class="block text-[9px] uppercase text-[#7f8993] mb-1.5">
                                    Transaction Reference
                                </label>

                                <input
                                    type="text"
                                    name="transaction_reference"
                                    class="w-full h-[36px] px-3 border border-[#dce1e6] rounded-[3px] text-[10px]"
                                >

                            </div>


                            <div>

                                <label class="block text-[9px] uppercase text-[#7f8993] mb-1.5">
                                    Paid At
                                </label>

                                <input
                                    type="datetime-local"
                                    name="paid_at"
                                    value="{{ $defaultPaidAt }}"
                                    class="w-full h-[36px] px-3 border border-[#dce1e6] rounded-[3px] text-[10px]"
                                >

                            </div>


                            <div>

                                <label class="block text-[9px] uppercase text-[#7f8993] mb-1.5">
                                    Notes
                                </label>

                                <textarea
                                    name="notes"
                                    rows="2"
                                    class="w-full px-3 py-2 border border-[#dce1e6] rounded-[3px] text-[10px] resize-none"
                                ></textarea>

                            </div>


                            <button
                                type="submit"
                                class="w-full h-[38px] bg-[#1677ff] text-white rounded-[3px] text-[10px] font-medium"
                            >
                                Record Payment
                            </button>

                        </div>

                    </form>

                </div>


            @elseif($outstandingBalance <= 0)

                <div class="p-5 bg-[#f5fbf7] border border-[#cfe9d7] rounded-[3px] text-center">

                    <div class="text-[18px] text-[#31845b]">
                        ✓
                    </div>

                    <div class="text-[12px] font-medium text-[#31845b] mt-2">
                        No Outstanding Balance
                    </div>

                </div>

            @endif


            {{-- ====================================================== --}}
            {{-- GUEST --}}
            {{-- ====================================================== --}}

            <div class="bg-white border border-[#e2e6ea] rounded-[3px] overflow-hidden">

                <div class="px-5 py-4 border-b border-[#edf0f2]">

                    <div class="text-[12px] font-medium text-[#36414c]">
                        Primary Guest
                    </div>

                    <div class="text-[9px] text-[#929ba4] mt-1">
                        Khách chính đứng tên đặt phòng
                    </div>

                </div>


                <div class="p-5">

                    @if($reservation->guest)

                        <div class="text-[12px] font-medium text-[#303942]">
                            {{ $reservation->guest->full_name }}
                        </div>

                        <div class="text-[9px] text-[#1677ff] mt-1">
                            {{ $reservation->guest->code }}
                        </div>


                        <div class="mt-4 space-y-3">

                            <div>

                                <div class="text-[9px] uppercase text-[#929ba4]">
                                    Phone
                                </div>

                                <div class="text-[10px] mt-1">
                                    {{ $reservation->guest->phone ?: '-' }}
                                </div>

                            </div>


                            <div>

                                <div class="text-[9px] uppercase text-[#929ba4]">
                                    Email
                                </div>

                                <div class="text-[10px] mt-1 break-all">
                                    {{ $reservation->guest->email ?: '-' }}
                                </div>

                            </div>

                        </div>

                    @endif

                </div>

            </div>


            {{-- ====================================================== --}}
            {{-- OPERATION --}}
            {{-- ====================================================== --}}

            <div class="bg-white border border-[#e2e6ea] rounded-[3px] overflow-hidden">

                <div class="px-5 py-4 border-b border-[#edf0f2]">

                    <div class="text-[12px] font-medium text-[#36414c]">
                        Operation
                    </div>

                    <div class="text-[9px] text-[#929ba4] mt-1">
                        Thông tin vận hành nhận phòng và trả phòng
                    </div>

                </div>


                <div class="p-5 space-y-3">

                    <div class="flex justify-between">

                        <span class="text-[9px] uppercase text-[#929ba4]">
                            Currency
                        </span>

                        <span class="text-[10px]">
                            {{ $currency }}
                        </span>

                    </div>


                    @if($reservation->checked_in_at)

                        <div class="flex justify-between">

                            <span class="text-[9px] uppercase text-[#929ba4]">
                                Checked In
                            </span>

                            <span class="text-[10px]">
                                {{ $reservation->checked_in_at->format('d/m/Y H:i') }}
                            </span>

                        </div>

                    @endif


                    @if($reservation->checked_out_at)

                        <div class="flex justify-between">

                            <span class="text-[9px] uppercase text-[#929ba4]">
                                Checked Out
                            </span>

                            <span class="text-[10px]">
                                {{ $reservation->checked_out_at->format('d/m/Y H:i') }}
                            </span>

                        </div>

                    @endif

                </div>

            </div>

        </div>

    </div>

</div>

@endsection