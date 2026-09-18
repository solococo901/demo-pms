@extends('layouts.pms')

@section('title', 'Room Move - ' . $reservation->code)

@section('content')

@php
    $currentRoom = $reservationRoom->room;

    $reasonLabels = [
        'guest_request' => [
            'en' => 'Guest Request',
            'vi' => 'Khách yêu cầu đổi phòng',
        ],

        'room_issue' => [
            'en' => 'Room Issue',
            'vi' => 'Phòng hiện tại có vấn đề',
        ],

        'maintenance' => [
            'en' => 'Maintenance',
            'vi' => 'Phòng cần bảo trì',
        ],

        'operational' => [
            'en' => 'Operational',
            'vi' => 'Điều chuyển do vận hành',
        ],

        'other' => [
            'en' => 'Other',
            'vi' => 'Lý do khác',
        ],
    ];
@endphp


<div class="w-full max-w-[1180px] mx-auto">

    {{-- HEADER --}}
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between mb-5">

        <div>

            <h1 class="text-[18px] font-medium text-[#303942]">
                Room Move
            </h1>

            <div class="text-[10px] text-[#929ba4] mt-1">
                Đổi phòng cho khách đang lưu trú
            </div>

            <div class="text-[9px] text-[#a0a8b0] mt-1">
                {{ $reservation->code }}
                ·
                {{ $reservation->guest?->full_name ?? '-' }}
            </div>

        </div>


        <div class="flex gap-2">

            <a
                href="{{ route('pms.reservations.show', $reservation) }}"
                class="h-[34px] px-4 inline-flex items-center justify-center border border-[#dce1e6] bg-white rounded-[3px] text-[10px] text-[#59636e]"
            >
                ← Reservation
            </a>


            <a
                href="{{ route('pms.front-desk.index') }}"
                class="h-[34px] px-4 inline-flex items-center justify-center bg-[#1677ff] text-white rounded-[3px] text-[10px]"
            >
                Front Desk
            </a>

        </div>

    </div>


    {{-- FLASH --}}
    @if(session('error'))

        <div class="mb-4 px-4 py-3 bg-[#fff3f3] border border-[#f1cccc] rounded-[3px]">

            <div class="text-[11px] text-[#b64646]">
                {{ session('error') }}
            </div>

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


    <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_360px] gap-4">

        {{-- LEFT --}}
        <div class="space-y-4">

            {{-- CURRENT ROOM --}}
            <div class="bg-white border border-[#e2e6ea] rounded-[3px] overflow-hidden">

                <div class="px-5 py-4 border-b border-[#edf0f2]">

                    <div class="text-[12px] font-medium text-[#36414c]">
                        Current Room
                    </div>

                    <div class="text-[9px] text-[#929ba4] mt-1">
                        Phòng khách đang lưu trú hiện tại
                    </div>

                </div>


                <div class="p-5">

                    <div class="flex items-center justify-between gap-4 p-4 bg-[#f7faff] border border-[#d9e8ff] rounded-[3px]">

                        <div>

                            <div class="text-[9px] uppercase tracking-wide text-[#929ba4]">
                                Physical Room
                            </div>

                            <div class="text-[24px] font-medium text-[#1677ff] mt-1">
                                {{ $currentRoom->room_number }}
                            </div>

                            <div class="text-[10px] text-[#59636e] mt-1">
                                {{ $reservationRoom->roomType?->name ?? '-' }}
                            </div>

                            @if($currentRoom->floor)

                                <div class="text-[9px] text-[#929ba4] mt-1">
                                    Floor {{ $currentRoom->floor }}
                                    ·
                                    Tầng {{ $currentRoom->floor }}
                                </div>

                            @endif

                        </div>


                        <div class="text-right">

                            <span class="inline-flex px-2 py-1 bg-[#eef5ff] text-[#1677ff] rounded-[3px] text-[9px]">
                                Occupied
                            </span>

                            <div class="text-[8px] text-[#929ba4] mt-1">
                                Đang có khách
                            </div>

                        </div>

                    </div>

                </div>

            </div>


            {{-- MOVE FORM --}}
            <div class="bg-white border border-[#e2e6ea] rounded-[3px] overflow-hidden">

                <div class="px-5 py-4 border-b border-[#edf0f2]">

                    <div class="text-[12px] font-medium text-[#36414c]">
                        Move To
                    </div>

                    <div class="text-[9px] text-[#929ba4] mt-1">
                        Chọn phòng mới cho khách
                    </div>

                </div>


                <form
                    method="POST"
                    action="{{ route('pms.room-moves.store', $reservation) }}"
                >
                    @csrf


                    <div class="p-5 space-y-5">

                        {{-- DESTINATION ROOM --}}
                        <div>

                            <label class="block text-[9px] uppercase tracking-wide text-[#7f8993] mb-1.5">
                                Destination Room *
                            </label>

                            <div class="text-[8px] text-[#a0a8b0] mb-2">
                                Phòng chuyển đến — chỉ hiển thị phòng sạch và đang sẵn sàng
                            </div>


                            @if($candidateRooms->isEmpty())

                                <div class="px-4 py-4 bg-[#fff7e8] border border-[#f0dfba] rounded-[3px]">

                                    <div class="text-[10px] font-medium text-[#9a6c28]">
                                        No eligible room available
                                    </div>

                                    <div class="text-[9px] text-[#a27b45] mt-1">
                                        Hiện chưa có phòng cùng loại phù hợp để chuyển khách.
                                    </div>

                                </div>

                            @else

                                <select
                                    name="to_room_id"
                                    required
                                    class="w-full h-[40px] px-3 border border-[#dce1e6] bg-white rounded-[3px] text-[11px] outline-none focus:border-[#1677ff]"
                                >

                                    <option value="">
                                        Select destination room
                                    </option>

                                    @foreach($candidateRooms as $room)

                                        <option
                                            value="{{ $room->id }}"
                                            @selected((int) old('to_room_id') === (int) $room->id)
                                        >
                                            Room {{ $room->room_number }}
                                            @if($room->floor)
                                                — Floor {{ $room->floor }}
                                            @endif
                                            — {{ ucfirst($room->housekeeping_status) }}
                                        </option>

                                    @endforeach

                                </select>

                            @endif

                        </div>


                        {{-- REASON --}}
                        <div>

                            <label class="block text-[9px] uppercase tracking-wide text-[#7f8993] mb-1.5">
                                Reason *
                            </label>

                            <div class="text-[8px] text-[#a0a8b0] mb-2">
                                Lý do đổi phòng
                            </div>


                            <select
                                name="reason"
                                required
                                class="w-full h-[40px] px-3 border border-[#dce1e6] bg-white rounded-[3px] text-[10px]"
                            >

                                <option value="">
                                    Select reason
                                </option>

                                @foreach($reasonLabels as $value => $label)

                                    <option
                                        value="{{ $value }}"
                                        @selected(old('reason') === $value)
                                    >
                                        {{ $label['en'] }}
                                        — {{ $label['vi'] }}
                                    </option>

                                @endforeach

                            </select>

                        </div>


                        {{-- NOTES --}}
                        <div>

                            <label class="block text-[9px] uppercase tracking-wide text-[#7f8993] mb-1.5">
                                Notes
                            </label>

                            <div class="text-[8px] text-[#a0a8b0] mb-2">
                                Ghi chú nội bộ về việc đổi phòng
                            </div>

                            <textarea
                                name="notes"
                                rows="4"
                                placeholder="Example: Air conditioner issue in Room {{ $currentRoom->room_number }}"
                                class="w-full px-3 py-2 border border-[#dce1e6] rounded-[3px] text-[10px] resize-none outline-none focus:border-[#1677ff]"
                            >{{ old('notes') }}</textarea>

                        </div>


                        {{-- INFO --}}
                        <div class="p-4 bg-[#f7faff] border border-[#d9e8ff] rounded-[3px]">

                            <div class="text-[10px] font-medium text-[#1677ff]">
                                What happens after Room Move?
                            </div>

                            <div class="text-[9px] text-[#697786] mt-2 leading-5">
                                Sau khi đổi phòng:
                                phòng cũ sẽ chuyển thành
                                <strong>Available + Dirty</strong>,
                                phòng mới thành
                                <strong>Occupied</strong>.
                                Inventory và Channex không thay đổi.
                            </div>

                        </div>


                        @if($candidateRooms->isNotEmpty())

                            <button
                                type="submit"
                                class="w-full h-[40px] bg-[#1677ff] text-white rounded-[3px] text-[10px] font-medium"
                                onclick="return confirm('Confirm Room Move for {{ $reservation->code }}?')"
                            >
                                Confirm Room Move
                            </button>

                            <div class="text-[8px] text-[#929ba4] text-center">
                                Xác nhận đổi phòng
                            </div>

                        @endif

                    </div>

                </form>

            </div>


            {{-- AVAILABLE ROOM CARDS --}}
            @if($candidateRooms->isNotEmpty())

                <div class="bg-white border border-[#e2e6ea] rounded-[3px] overflow-hidden">

                    <div class="px-5 py-4 border-b border-[#edf0f2]">

                        <div class="text-[12px] font-medium text-[#36414c]">
                            Eligible Rooms
                        </div>

                        <div class="text-[9px] text-[#929ba4] mt-1">
                            Các phòng đủ điều kiện để chuyển khách
                        </div>

                    </div>


                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">

                        @foreach($candidateRooms as $room)

                            <div class="p-4 border-r border-b border-[#edf0f2]">

                                <div class="flex items-start justify-between gap-3">

                                    <div>

                                        <div class="text-[18px] font-medium text-[#303942]">
                                            {{ $room->room_number }}
                                        </div>

                                        <div class="text-[9px] text-[#929ba4] mt-1">
                                            {{ $reservationRoom->roomType?->name }}
                                        </div>

                                        @if($room->floor)

                                            <div class="text-[9px] text-[#929ba4] mt-1">
                                                Floor {{ $room->floor }}
                                            </div>

                                        @endif

                                    </div>


                                    <div class="text-right">

                                        <span class="px-2 py-1 bg-[#edf9f1] text-[#31845b] rounded-[3px] text-[8px]">
                                            {{ ucfirst($room->housekeeping_status) }}
                                        </span>

                                        <div class="text-[8px] text-[#929ba4] mt-1">
                                            Sẵn sàng
                                        </div>

                                    </div>

                                </div>

                            </div>

                        @endforeach

                    </div>

                </div>

            @endif

        </div>


        {{-- RIGHT --}}
        <div class="space-y-4">

            {{-- RESERVATION INFO --}}
            <div class="bg-white border border-[#e2e6ea] rounded-[3px] overflow-hidden">

                <div class="px-5 py-4 border-b border-[#edf0f2]">

                    <div class="text-[12px] font-medium text-[#36414c]">
                        Reservation
                    </div>

                    <div class="text-[9px] text-[#929ba4] mt-1">
                        Thông tin đặt phòng
                    </div>

                </div>


                <div class="p-5 space-y-4">

                    <div>

                        <div class="text-[9px] uppercase text-[#929ba4]">
                            Reservation Code
                        </div>

                        <div class="text-[11px] font-medium text-[#1677ff] mt-1">
                            {{ $reservation->code }}
                        </div>

                    </div>


                    <div>

                        <div class="text-[9px] uppercase text-[#929ba4]">
                            Guest
                        </div>

                        <div class="text-[11px] text-[#36414c] mt-1">
                            {{ $reservation->guest?->full_name ?? '-' }}
                        </div>

                        <div class="text-[8px] text-[#929ba4] mt-0.5">
                            Khách lưu trú
                        </div>

                    </div>


                    <div>

                        <div class="text-[9px] uppercase text-[#929ba4]">
                            Room Type
                        </div>

                        <div class="text-[11px] text-[#36414c] mt-1">
                            {{ $reservationRoom->roomType?->name ?? '-' }}
                        </div>

                        <div class="text-[8px] text-[#929ba4] mt-0.5">
                            Loại phòng đã đặt
                        </div>

                    </div>


                    <div>

                        <div class="text-[9px] uppercase text-[#929ba4]">
                            Check-out
                        </div>

                        <div class="text-[11px] text-[#36414c] mt-1">
                            {{ \Carbon\CarbonImmutable::parse($reservationRoom->check_out)->format('d/m/Y') }}
                        </div>

                    </div>

                </div>

            </div>


            {{-- MOVE HISTORY --}}
            <div class="bg-white border border-[#e2e6ea] rounded-[3px] overflow-hidden">

                <div class="px-5 py-4 border-b border-[#edf0f2]">

                    <div class="text-[12px] font-medium text-[#36414c]">
                        Room Move History
                    </div>

                    <div class="text-[9px] text-[#929ba4] mt-1">
                        Lịch sử đổi phòng
                    </div>

                </div>


                @if($roomMoves->isEmpty())

                    <div class="p-6 text-center">

                        <div class="text-[10px] text-[#929ba4]">
                            No room move history.
                        </div>

                        <div class="text-[8px] text-[#a0a8b0] mt-1">
                            Khách chưa từng đổi phòng.
                        </div>

                    </div>

                @else

                    <div class="divide-y divide-[#edf0f2]">

                        @foreach($roomMoves as $move)

                            <div class="p-4">

                                <div class="flex items-center justify-between gap-3">

                                    <span class="text-[9px] font-medium text-[#1677ff]">
                                        {{ $move->code }}
                                    </span>

                                    <span class="text-[8px] text-[#929ba4]">
                                        {{ $move->moved_at?->format('d/m/Y H:i') }}
                                    </span>

                                </div>


                                <div class="flex items-center gap-3 mt-3">

                                    <div class="px-3 py-2 bg-[#fff0f0] text-[#b64646] rounded-[3px] text-[12px] font-medium">
                                        {{ $move->from_room_number }}
                                    </div>

                                    <span class="text-[#929ba4]">
                                        →
                                    </span>

                                    <div class="px-3 py-2 bg-[#edf9f1] text-[#31845b] rounded-[3px] text-[12px] font-medium">
                                        {{ $move->to_room_number }}
                                    </div>

                                </div>


                                <div class="text-[9px] text-[#59636e] mt-3">
                                    {{ $reasonLabels[$move->reason]['en'] ?? ucwords(str_replace('_', ' ', $move->reason ?? 'Other')) }}
                                </div>

                                <div class="text-[8px] text-[#929ba4] mt-0.5">
                                    {{ $reasonLabels[$move->reason]['vi'] ?? 'Lý do đổi phòng' }}
                                </div>


                                @if($move->notes)

                                    <div class="mt-2 text-[9px] text-[#7c8791] leading-4">
                                        {{ $move->notes }}
                                    </div>

                                @endif

                            </div>

                        @endforeach

                    </div>

                @endif

            </div>

        </div>

    </div>

</div>

@endsection