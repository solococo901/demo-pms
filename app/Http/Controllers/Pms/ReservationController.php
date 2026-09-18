<?php

namespace App\Http\Controllers\Pms;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use App\Models\Property;
use App\Models\RatePlan;
use App\Models\Reservation;
use App\Models\ReservationRoom;
use App\Models\Room;
use App\Models\RoomType;
use App\Services\ChannexService;
use App\Services\ReservationInventoryService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ReservationController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Reservation List
    |--------------------------------------------------------------------------
    */
    public function index(Request $request)
    {
        $property = Property::firstOrFail();

        $query = Reservation::with([
            'guest',
            'rooms.roomType',
            'rooms.ratePlan',
            'rooms.room',
        ])
            ->where(
                'property_id',
                $property->id
            );


        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */
        if ($request->filled('search')) {

            $search = trim(
                $request->search
            );

            $query->where(function ($q) use ($search) {

                $q->where(
                    'code',
                    'like',
                    '%' . $search . '%'
                )
                    ->orWhere(
                        'external_reservation_id',
                        'like',
                        '%' . $search . '%'
                    )
                    ->orWhereHas(
                        'guest',
                        function ($guestQuery) use ($search) {

                            $guestQuery
                                ->where(
                                    'first_name',
                                    'like',
                                    '%' . $search . '%'
                                )
                                ->orWhere(
                                    'last_name',
                                    'like',
                                    '%' . $search . '%'
                                )
                                ->orWhere(
                                    'phone',
                                    'like',
                                    '%' . $search . '%'
                                )
                                ->orWhere(
                                    'email',
                                    'like',
                                    '%' . $search . '%'
                                );
                        }
                    );
            });
        }


        /*
        |--------------------------------------------------------------------------
        | Status Filter
        |--------------------------------------------------------------------------
        */
        if ($request->filled('status')) {

            $query->where(
                'status',
                $request->status
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Payment Filter
        |--------------------------------------------------------------------------
        */
        if ($request->filled('payment_status')) {

            $query->where(
                'payment_status',
                $request->payment_status
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Source Filter
        |--------------------------------------------------------------------------
        */
        if ($request->filled('source')) {

            $query->where(
                'source',
                $request->source
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Result
        |--------------------------------------------------------------------------
        */
        $reservations = $query
            ->orderByDesc('id')
            ->get();


        return view(
            'pms.reservations.index',
            compact(
                'property',
                'reservations'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Create Form
    |--------------------------------------------------------------------------
    */
    public function create()
    {
        $property = Property::firstOrFail();


        $guests = Guest::where(
            'property_id',
            $property->id
        )
            ->where(
                'status',
                'active'
            )
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();


        $roomTypes = RoomType::where(
            'property_id',
            $property->id
        )
            ->where(
                'status',
                'active'
            )
            ->orderBy('name')
            ->get();


        $ratePlans = RatePlan::with('roomType')
            ->where(
                'property_id',
                $property->id
            )
            ->where(
                'status',
                'active'
            )
            ->orderBy('room_type_id')
            ->orderBy('name')
            ->get();


        return view(
            'pms.reservations.create',
            compact(
                'property',
                'guests',
                'roomTypes',
                'ratePlans'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Store Reservation
    |--------------------------------------------------------------------------
    */
    public function store(
        Request $request,
        ReservationInventoryService $inventoryService,
        ChannexService $channex
    ) {
        $property = Property::firstOrFail();


        /*
        |--------------------------------------------------------------------------
        | Validate
        |--------------------------------------------------------------------------
        */
        $validated = $this->validateReservation(
            $request,
            $property
        );


        /*
        |--------------------------------------------------------------------------
        | Room Type
        |--------------------------------------------------------------------------
        */
        $roomType = RoomType::where(
            'property_id',
            $property->id
        )
            ->findOrFail(
                $validated['room_type_id']
            );


        /*
        |--------------------------------------------------------------------------
        | Rate Plan
        |--------------------------------------------------------------------------
        */
        $ratePlan = null;

        if (
            !empty(
            $validated['rate_plan_id']
        )
        ) {

            $ratePlan = RatePlan::where(
                'property_id',
                $property->id
            )
                ->where(
                    'room_type_id',
                    $roomType->id
                )
                ->findOrFail(
                    $validated['rate_plan_id']
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Dates
        |--------------------------------------------------------------------------
        */
        $checkIn = CarbonImmutable::parse(
            $validated['check_in']
        );

        $checkOut = CarbonImmutable::parse(
            $validated['check_out']
        );


        $nights = $checkIn->diffInDays(
            $checkOut
        );


        if ($nights < 1) {

            throw ValidationException::withMessages([
                'check_out' =>
                    'Check-out must be after check-in.',
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Occupancy
        |--------------------------------------------------------------------------
        */
        $this->validateOccupancy(
            $roomType,
            (int) $validated['adults'],
            (int) $validated['children']
        );


        /*
        |--------------------------------------------------------------------------
        | Nightly Rate
        |--------------------------------------------------------------------------
        */
        $nightlyRate =
            isset($validated['nightly_rate'])
            &&
            $validated['nightly_rate'] !== null
            ? (float) $validated['nightly_rate']
            : (
                $ratePlan
                ? (float) $ratePlan->base_rate
                : (float) $roomType->base_price
            );


        /*
        |--------------------------------------------------------------------------
        | Pricing
        |--------------------------------------------------------------------------
        */
        $roomTotal =
            $nightlyRate
            *
            $nights;


        $taxAmount =
            (float) (
                $validated['tax_amount']
                ?? 0
            );


        $feeAmount =
            (float) (
                $validated['fee_amount']
                ?? 0
            );


        $subtotal =
            $roomTotal;


        $totalAmount =
            $subtotal
            +
            $taxAmount
            +
            $feeAmount;


        /*
        |--------------------------------------------------------------------------
        | Create Booking + Reserve Inventory
        |--------------------------------------------------------------------------
        */
        $result = DB::transaction(
            function () use ($property, $validated, $roomType, $ratePlan, $nightlyRate, $roomTotal, $subtotal, $taxAmount, $feeAmount, $totalAmount, $inventoryService) {

                /*
                |--------------------------------------------------------------------------
                | Reservation
                |--------------------------------------------------------------------------
                */
                $reservation =
                    Reservation::create([
                        'property_id' =>
                            $property->id,

                        'guest_id' =>
                            $validated['guest_id'],

                        'code' =>
                            $this->generateReservationCode(
                                $property
                            ),

                        'source' =>
                            $validated['source'],

                        'channel' =>
                            $validated['channel']
                            ?? null,

                        'external_reservation_id' =>
                            $validated['external_reservation_id']
                            ?? null,

                        'status' =>
                            $validated['status'],

                        'payment_status' =>
                            'unpaid',

                        'currency' =>
                            $property->currency
                            ?? 'VND',

                        'subtotal' =>
                            $subtotal,

                        'tax_amount' =>
                            $taxAmount,

                        'fee_amount' =>
                            $feeAmount,

                        'total_amount' =>
                            $totalAmount,

                        'paid_amount' =>
                            0,

                        'special_requests' =>
                            $validated['special_requests']
                            ?? null,

                        'notes' =>
                            $validated['notes']
                            ?? null,

                        'booked_at' =>
                            now(),

                        'cancelled_at' =>
                            $validated['status']
                            ===
                            'cancelled'
                            ? now()
                            : null,
                    ]);


                /*
                |--------------------------------------------------------------------------
                | Reservation Room
                |--------------------------------------------------------------------------
                */
                ReservationRoom::create([
                    'reservation_id' =>
                        $reservation->id,

                    'room_type_id' =>
                        $roomType->id,

                    'rate_plan_id' =>
                        $ratePlan?->id,

                    'room_id' =>
                        null,

                    'check_in' =>
                        $validated['check_in'],

                    'check_out' =>
                        $validated['check_out'],

                    'adults' =>
                        $validated['adults'],

                    'children' =>
                        $validated['children'],

                    'nightly_rate' =>
                        $nightlyRate,

                    'total_amount' =>
                        $roomTotal,
                ]);


                /*
                |--------------------------------------------------------------------------
                | Reserve Inventory
                |--------------------------------------------------------------------------
                */
                $inventoryIds = [];


                if (
                    $inventoryService
                        ->holdsInventory(
                            $reservation->status
                        )
                ) {

                    $inventoryIds =
                        $inventoryService
                            ->reserve(
                                $property,
                                $roomType,
                                $validated['check_in'],
                                $validated['check_out']
                            );
                }


                return [
                    'reservation' =>
                        $reservation,

                    'inventory_ids' =>
                        $inventoryIds,
                ];
            }
        );


        $reservation =
            $result['reservation'];


        $inventoryIds =
            $result['inventory_ids'];


        /*
        |--------------------------------------------------------------------------
        | Sync Channex AFTER Local Transaction
        |--------------------------------------------------------------------------
        */
        $syncResult =
            $inventoryService
                ->syncToChannex(
                    $property,
                    $inventoryIds,
                    $channex
                );


        /*
        |--------------------------------------------------------------------------
        | Warning / Failed
        |--------------------------------------------------------------------------
        */
        if (
            in_array(
                $syncResult['status'] ?? '',
                [
                    'failed',
                    'warning',
                ],
                true
            )
        ) {

            return redirect()
                ->route(
                    'pms.reservations.show',
                    $reservation
                )
                ->with(
                    'success',
                    'Reservation created successfully.'
                )
                ->with(
                    'error',
                    $syncResult['message']
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Success
        |--------------------------------------------------------------------------
        */
        return redirect()
            ->route(
                'pms.reservations.show',
                $reservation
            )
            ->with(
                'success',
                'Reservation created successfully. '
                . (
                    $syncResult['message']
                    ?? ''
                )
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Reservation Detail
    |--------------------------------------------------------------------------
    */
    public function show(
        Reservation $reservation
    ) {
        /*
        |--------------------------------------------------------------------------
        | Property
        |--------------------------------------------------------------------------
        */
        $property = Property::firstOrFail();


        /*
        |--------------------------------------------------------------------------
        | Security
        |--------------------------------------------------------------------------
        */
        abort_if(
            (int) $reservation->property_id
            !==
            (int) $property->id,
            404
        );


        /*
        |--------------------------------------------------------------------------
        | Load Reservation
        |--------------------------------------------------------------------------
        */
        $reservation->load([
            'guest',
            'rooms.roomType',
            'rooms.ratePlan',
            'rooms.room',

            'payments' => function ($query) {
                $query
                    ->with([
                        'refunds' => function ($refundQuery) {
                            $refundQuery
                                ->orderByDesc('refunded_at')
                                ->orderByDesc('id');
                        },
                    ])
                    ->orderByDesc('paid_at')
                    ->orderByDesc('id');
            },

            'refunds' => function ($query) {
                $query
                    ->with('payment')
                    ->orderByDesc('refunded_at')
                    ->orderByDesc('id');
            },

            'folioItems' => function ($query) {
                $query
                    ->orderByDesc('posted_at')
                    ->orderByDesc('id');
            },

            'roomMoves' => function ($query) {

                $query
                    ->with([
                        'fromRoom',
                        'toRoom',
                    ])
                    ->orderByDesc('moved_at')
                    ->orderByDesc('id');
            },
        ]);


        /*
        |--------------------------------------------------------------------------
        | Phase 1 Room Line
        |--------------------------------------------------------------------------
        */
        $reservationRoom =
            $reservation
                ->rooms
                ->first();


        /*
        |--------------------------------------------------------------------------
        | Available Physical Rooms
        |--------------------------------------------------------------------------
        */
        $availableRooms =
            collect();


        if (
            $reservationRoom
            &&
            $reservationRoom->roomType
        ) {

            /*
            |--------------------------------------------------------------------------
            | Rooms Of Same Room Type
            |--------------------------------------------------------------------------
            */
            $rooms = Room::where(
                'property_id',
                $property->id
            )
                ->where(
                    'room_type_id',
                    $reservationRoom->room_type_id
                )
                ->whereNotIn(
                    'status',
                    [
                        'maintenance',
                        'out_of_order',
                    ]
                )
                ->orderBy('floor')
                ->orderBy('room_number')
                ->get();


            /*
            |--------------------------------------------------------------------------
            | Remove Rooms With Booking Conflict
            |--------------------------------------------------------------------------
            */
            $availableRooms =
                $rooms
                    ->filter(
                        function ($room) use ($reservationRoom) {

                            $hasConflict =
                                ReservationRoom::where(
                                    'room_id',
                                    $room->id
                                )
                                    ->where(
                                        'id',
                                        '!=',
                                        $reservationRoom->id
                                    )
                                    ->where(
                                        'check_in',
                                        '<',
                                        $reservationRoom->check_out
                                    )
                                    ->where(
                                        'check_out',
                                        '>',
                                        $reservationRoom->check_in
                                    )
                                    ->whereHas(
                                        'reservation',
                                        function ($query) {
                                            $query->whereNotIn(
                                                'status',
                                                [
                                                    'cancelled',
                                                    'no_show',
                                                ]
                                            );
                                        }
                                    )
                                    ->exists();


                            return !$hasConflict;
                        }
                    )
                    ->values();
        }


        /*
        |--------------------------------------------------------------------------
        | Gross Paid
        |--------------------------------------------------------------------------
        | Tổng toàn bộ payment completed.
        */
        $grossPaid =
            (float) $reservation
                ->payments
                ->where(
                    'status',
                    'completed'
                )
                ->sum(
                    'amount'
                );


        /*
        |--------------------------------------------------------------------------
        | Refunded Amount
        |--------------------------------------------------------------------------
        | Chỉ tính refund completed.
        */
        $refundedAmount =
            (float) $reservation
                ->refunds
                ->where(
                    'status',
                    'completed'
                )
                ->sum(
                    'amount'
                );


        /*
        |--------------------------------------------------------------------------
        | Net Paid
        |--------------------------------------------------------------------------
        | Gross Paid - Refunded
        */
        $calculatedPaidAmount =
            max(
                0,
                $grossPaid
                -
                $refundedAmount
            );


        /*
        |--------------------------------------------------------------------------
        | Total Reservation
        |--------------------------------------------------------------------------
        */
        $totalAmount =
            (float) $reservation
                ->total_amount;


        /*
        |--------------------------------------------------------------------------
        | Balance
        |--------------------------------------------------------------------------
        | Total - Net Paid
        */
        $balance =
            max(
                0,
                $totalAmount
                -
                $calculatedPaidAmount
            );


        /*
        |--------------------------------------------------------------------------
        | Payment Status
        |--------------------------------------------------------------------------
        */
        if (
            $calculatedPaidAmount
            <=
            0
        ) {

            if (
                $grossPaid
                >
                0
                &&
                $refundedAmount
                >
                0
            ) {
                $calculatedPaymentStatus =
                    'refunded';
            } else {
                $calculatedPaymentStatus =
                    'unpaid';
            }

        } elseif (
            $calculatedPaidAmount
            <
            $totalAmount
        ) {
            $calculatedPaymentStatus =
                'partial';
        } else {
            $calculatedPaymentStatus =
                'paid';
        }


        /*
        |--------------------------------------------------------------------------
        | Keep Reservation Payment Summary In Sync
        |--------------------------------------------------------------------------
        | paid_amount luôn là NET PAID.
        */
        if (
            (float) $reservation->paid_amount
            !==
            (float) $calculatedPaidAmount
            ||
            $reservation->payment_status
            !==
            $calculatedPaymentStatus
        ) {
            $reservation->update([
                'paid_amount' =>
                    $calculatedPaidAmount,

                'payment_status' =>
                    $calculatedPaymentStatus,
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | View
        |--------------------------------------------------------------------------
        */
        return view(
            'pms.reservations.show',
            compact(
                'property',
                'reservation',
                'reservationRoom',
                'availableRooms',
                'grossPaid',
                'refundedAmount',
                'calculatedPaidAmount',
                'balance',
                'calculatedPaymentStatus'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Assign Physical Room
    |--------------------------------------------------------------------------
    */
    public function assignRoom(
        Request $request,
        Reservation $reservation
    ) {
        $property = Property::firstOrFail();


        /*
        |--------------------------------------------------------------------------
        | Security
        |--------------------------------------------------------------------------
        */
        abort_if(
            $reservation->property_id
            !==
            $property->id,
            404
        );


        /*
        |--------------------------------------------------------------------------
        | Reservation Status
        |--------------------------------------------------------------------------
        */
        if (
            in_array(
                $reservation->status,
                [
                    'cancelled',
                    'no_show',
                    'checked_out',
                ],
                true
            )
        ) {

            return redirect()
                ->route(
                    'pms.reservations.show',
                    $reservation
                )
                ->with(
                    'error',
                    'This reservation cannot receive a room assignment.'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Reservation Room Line
        |--------------------------------------------------------------------------
        */
        $reservationRoom =
            $reservation
                ->rooms()
                ->firstOrFail();


        /*
        |--------------------------------------------------------------------------
        | Validate
        |--------------------------------------------------------------------------
        */
        $validated =
            $request->validate([
                'room_id' => [
                    'required',
                    'integer',
                    'exists:rooms,id',
                ],
            ]);


        /*
        |--------------------------------------------------------------------------
        | Physical Room
        |--------------------------------------------------------------------------
        */
        $room = Room::where(
            'property_id',
            $property->id
        )
            ->findOrFail(
                $validated['room_id']
            );


        /*
        |--------------------------------------------------------------------------
        | Room Type Must Match
        |--------------------------------------------------------------------------
        */
        if (
            (int) $room->room_type_id
            !==
            (int) $reservationRoom->room_type_id
        ) {

            throw ValidationException::withMessages([
                'room_id' =>
                    'Physical Room does not belong to the reserved Room Type.',
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Room Operational Status
        |--------------------------------------------------------------------------
        */
        if (
            in_array(
                $room->status,
                [
                    'maintenance',
                    'out_of_order',
                ],
                true
            )
        ) {

            throw ValidationException::withMessages([
                'room_id' =>
                    'This room is currently unavailable for assignment.',
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Booking Conflict
        |--------------------------------------------------------------------------
        */
        $hasConflict =
            ReservationRoom::where(
                'room_id',
                $room->id
            )
                ->where(
                    'id',
                    '!=',
                    $reservationRoom->id
                )
                ->where(
                    'check_in',
                    '<',
                    $reservationRoom->check_out
                )
                ->where(
                    'check_out',
                    '>',
                    $reservationRoom->check_in
                )
                ->whereHas(
                    'reservation',
                    function ($query) {

                        $query->whereNotIn(
                            'status',
                            [
                                'cancelled',
                                'no_show',
                            ]
                        );
                    }
                )
                ->exists();


        /*
        |--------------------------------------------------------------------------
        | Conflict
        |--------------------------------------------------------------------------
        */
        if ($hasConflict) {

            throw ValidationException::withMessages([
                'room_id' =>
                    "Room {$room->room_number} is already assigned to another reservation for these dates.",
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Assign Room
        |--------------------------------------------------------------------------
        */
        $reservationRoom->update([
            'room_id' =>
                $room->id,
        ]);


        return redirect()
            ->route(
                'pms.reservations.show',
                $reservation
            )
            ->with(
                'success',
                "Room {$room->room_number} assigned successfully."
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Unassign Physical Room
    |--------------------------------------------------------------------------
    */
    public function unassignRoom(
        Reservation $reservation
    ) {
        $property = Property::firstOrFail();


        /*
        |--------------------------------------------------------------------------
        | Security
        |--------------------------------------------------------------------------
        */
        abort_if(
            $reservation->property_id
            !==
            $property->id,
            404
        );


        /*
        |--------------------------------------------------------------------------
        | Checked In
        |--------------------------------------------------------------------------
        |
        | Sau này khi khách đã check-in,
        | muốn đổi phòng phải dùng Room Move.
        |
        */
        if (
            $reservation->status
            ===
            'checked_in'
        ) {

            return redirect()
                ->route(
                    'pms.reservations.show',
                    $reservation
                )
                ->with(
                    'error',
                    'Checked-in reservation cannot be unassigned. Use Room Move instead.'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Checked Out
        |--------------------------------------------------------------------------
        */
        if (
            $reservation->status
            ===
            'checked_out'
        ) {

            return redirect()
                ->route(
                    'pms.reservations.show',
                    $reservation
                )
                ->with(
                    'error',
                    'Checked-out reservation cannot be changed.'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Reservation Room
        |--------------------------------------------------------------------------
        */
        $reservationRoom =
            $reservation
                ->rooms()
                ->with('room')
                ->firstOrFail();


        /*
        |--------------------------------------------------------------------------
        | Nothing Assigned
        |--------------------------------------------------------------------------
        */
        if (
            !$reservationRoom->room_id
        ) {

            return redirect()
                ->route(
                    'pms.reservations.show',
                    $reservation
                )
                ->with(
                    'success',
                    'Reservation has no physical room assigned.'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Old Room
        |--------------------------------------------------------------------------
        */
        $oldRoom =
            $reservationRoom->room;


        /*
        |--------------------------------------------------------------------------
        | Unassign
        |--------------------------------------------------------------------------
        */
        $reservationRoom->update([
            'room_id' =>
                null,
        ]);


        return redirect()
            ->route(
                'pms.reservations.show',
                $reservation
            )
            ->with(
                'success',
                $oldRoom
                ? "Room {$oldRoom->room_number} unassigned successfully."
                : 'Room unassigned successfully.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Edit Reservation
    |--------------------------------------------------------------------------
    */
    public function edit(
        Reservation $reservation
    ) {
        $property = Property::firstOrFail();


        abort_if(
            $reservation->property_id
            !==
            $property->id,
            404
        );


        $reservation->load([
            'guest',
            'rooms.roomType',
            'rooms.ratePlan',
            'rooms.room',
        ]);


        $guests = Guest::where(
            'property_id',
            $property->id
        )
            ->where(
                'status',
                'active'
            )
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();


        $roomTypes = RoomType::where(
            'property_id',
            $property->id
        )
            ->where(
                'status',
                'active'
            )
            ->orderBy('name')
            ->get();


        $ratePlans = RatePlan::with(
            'roomType'
        )
            ->where(
                'property_id',
                $property->id
            )
            ->where(
                'status',
                'active'
            )
            ->orderBy('room_type_id')
            ->orderBy('name')
            ->get();


        return view(
            'pms.reservations.edit',
            compact(
                'property',
                'reservation',
                'guests',
                'roomTypes',
                'ratePlans'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Update Reservation
    |--------------------------------------------------------------------------
    */
    public function update(
        Request $request,
        Reservation $reservation,
        ReservationInventoryService $inventoryService,
        ChannexService $channex
    ) {
        $property = Property::firstOrFail();


        abort_if(
            $reservation->property_id
            !==
            $property->id,
            404
        );


        /*
        |--------------------------------------------------------------------------
        | Current Room Line
        |--------------------------------------------------------------------------
        */
        $reservationRoom =
            $reservation
                ->rooms()
                ->with('roomType')
                ->first();


        /*
        |--------------------------------------------------------------------------
        | Snapshot Old Data
        |--------------------------------------------------------------------------
        */
        $oldStatus =
            $reservation->status;


        $oldRoomType =
            $reservationRoom
            ? $reservationRoom->roomType
            : null;


        $oldCheckIn =
            $reservationRoom?->check_in;


        $oldCheckOut =
            $reservationRoom?->check_out;


        /*
        |--------------------------------------------------------------------------
        | Validate New Data
        |--------------------------------------------------------------------------
        */
        $validated = $this->validateReservation(
            $request,
            $property
        );


        /*
        |--------------------------------------------------------------------------
        | New Room Type
        |--------------------------------------------------------------------------
        */
        $roomType = RoomType::where(
            'property_id',
            $property->id
        )
            ->findOrFail(
                $validated['room_type_id']
            );


        /*
        |--------------------------------------------------------------------------
        | New Rate Plan
        |--------------------------------------------------------------------------
        */
        $ratePlan = null;

        if (
            !empty(
            $validated['rate_plan_id']
        )
        ) {

            $ratePlan = RatePlan::where(
                'property_id',
                $property->id
            )
                ->where(
                    'room_type_id',
                    $roomType->id
                )
                ->findOrFail(
                    $validated['rate_plan_id']
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Dates
        |--------------------------------------------------------------------------
        */
        $checkIn =
            CarbonImmutable::parse(
                $validated['check_in']
            );


        $checkOut =
            CarbonImmutable::parse(
                $validated['check_out']
            );


        $nights =
            $checkIn->diffInDays(
                $checkOut
            );


        if ($nights < 1) {

            throw ValidationException::withMessages([
                'check_out' =>
                    'Check-out must be after check-in.',
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Occupancy
        |--------------------------------------------------------------------------
        */
        $this->validateOccupancy(
            $roomType,
            (int) $validated['adults'],
            (int) $validated['children']
        );


        /*
        |--------------------------------------------------------------------------
        | Nightly Rate
        |--------------------------------------------------------------------------
        */
        $nightlyRate =
            isset($validated['nightly_rate'])
            &&
            $validated['nightly_rate'] !== null
            ? (float) $validated['nightly_rate']
            : (
                $ratePlan
                ? (float) $ratePlan->base_rate
                : (float) $roomType->base_price
            );


        /*
        |--------------------------------------------------------------------------
        | Pricing
        |--------------------------------------------------------------------------
        */
        $roomTotal =
            $nightlyRate
            *
            $nights;


        $taxAmount =
            (float) (
                $validated['tax_amount']
                ?? 0
            );


        $feeAmount =
            (float) (
                $validated['fee_amount']
                ?? 0
            );


        $subtotal =
            $roomTotal;


        /*
        |--------------------------------------------------------------------------
        | Existing Active Folio Charges
        |--------------------------------------------------------------------------
        | Khi sửa Reservation, giữ lại các chi phí phát sinh đang active.
        */
        $extraCharges =
            (float) $reservation
                ->folioItems()
                ->where(
                    'status',
                    'active'
                )
                ->sum(
                    'total_amount'
                );


        $totalAmount =
            $subtotal
            +
            $taxAmount
            +
            $feeAmount
            +
            $extraCharges;


        /*
        |--------------------------------------------------------------------------
        | Old Holds Inventory
        |--------------------------------------------------------------------------
        */
        $oldHoldsInventory =
            $inventoryService
                ->holdsInventory(
                    $oldStatus
                );


        /*
        |--------------------------------------------------------------------------
        | New Holds Inventory
        |--------------------------------------------------------------------------
        */
        $newHoldsInventory =
            $inventoryService
                ->holdsInventory(
                    $validated['status']
                );


        /*
        |--------------------------------------------------------------------------
        | Stay Changed
        |--------------------------------------------------------------------------
        */
        $stayChanged =
            !$reservationRoom
            ||
            (int) $reservationRoom->room_type_id
            !==
            (int) $roomType->id
            ||
            $oldCheckIn
            !==
            $validated['check_in']
            ||
            $oldCheckOut
            !==
            $validated['check_out'];


        /*
        |--------------------------------------------------------------------------
        | Update Transaction
        |--------------------------------------------------------------------------
        */
        $inventoryIds = DB::transaction(
            function () use ($reservation, $reservationRoom, $property, $validated, $roomType, $ratePlan, $nightlyRate, $roomTotal, $subtotal, $taxAmount, $feeAmount, $totalAmount, $inventoryService, $oldHoldsInventory, $newHoldsInventory, $stayChanged, $oldRoomType, $oldCheckIn, $oldCheckOut) {

                $inventoryIds = [];


                /*
                |--------------------------------------------------------------------------
                | Release Old Inventory
                |--------------------------------------------------------------------------
                */
                if (
                    $oldHoldsInventory
                    &&
                    $oldRoomType
                    &&
                    $oldCheckIn
                    &&
                    $oldCheckOut
                    &&
                    (
                        !$newHoldsInventory
                        ||
                        $stayChanged
                    )
                ) {

                    $releasedIds =
                        $inventoryService
                            ->release(
                                $property,
                                $oldRoomType,
                                $oldCheckIn,
                                $oldCheckOut
                            );


                    $inventoryIds =
                        array_merge(
                            $inventoryIds,
                            $releasedIds
                        );
                }


                /*
                |--------------------------------------------------------------------------
                | Reserve New Inventory
                |--------------------------------------------------------------------------
                */
                if (
                    $newHoldsInventory
                    &&
                    (
                        !$oldHoldsInventory
                        ||
                        $stayChanged
                    )
                ) {

                    $reservedIds =
                        $inventoryService
                            ->reserve(
                                $property,
                                $roomType,
                                $validated['check_in'],
                                $validated['check_out']
                            );


                    $inventoryIds =
                        array_merge(
                            $inventoryIds,
                            $reservedIds
                        );
                }


                /*
                |--------------------------------------------------------------------------
                | Update Reservation
                |--------------------------------------------------------------------------
                */
                $reservation->update([
                    'guest_id' =>
                        $validated['guest_id'],

                    'source' =>
                        $validated['source'],

                    'channel' =>
                        $validated['channel']
                        ?? null,

                    'external_reservation_id' =>
                        $validated['external_reservation_id']
                        ?? null,

                    'status' =>
                        $validated['status'],

                    'subtotal' =>
                        $subtotal,

                    'tax_amount' =>
                        $taxAmount,

                    'fee_amount' =>
                        $feeAmount,

                    'total_amount' =>
                        $totalAmount,

                    'special_requests' =>
                        $validated['special_requests']
                        ?? null,

                    'notes' =>
                        $validated['notes']
                        ?? null,

                    'cancelled_at' =>
                        $validated['status']
                        ===
                        'cancelled'
                        ? (
                            $reservation->cancelled_at
                            ??
                            now()
                        )
                        : null,
                ]);


                /*
                |--------------------------------------------------------------------------
                | Physical Room
                |--------------------------------------------------------------------------
                |
                | Nếu đổi Room Type:
                | bỏ Physical Room cũ.
                |
                */
                $physicalRoomId =
                    $reservationRoom?->room_id;


                if (
                    $reservationRoom
                    &&
                    (int) $reservationRoom->room_type_id
                    !==
                    (int) $roomType->id
                ) {

                    $physicalRoomId =
                        null;
                }


                /*
                |--------------------------------------------------------------------------
                | Update Reservation Room
                |--------------------------------------------------------------------------
                */
                if ($reservationRoom) {

                    $reservationRoom->update([
                        'room_type_id' =>
                            $roomType->id,

                        'rate_plan_id' =>
                            $ratePlan?->id,

                        'room_id' =>
                            $physicalRoomId,

                        'check_in' =>
                            $validated['check_in'],

                        'check_out' =>
                            $validated['check_out'],

                        'adults' =>
                            $validated['adults'],

                        'children' =>
                            $validated['children'],

                        'nightly_rate' =>
                            $nightlyRate,

                        'total_amount' =>
                            $roomTotal,
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Missing Reservation Room
                |--------------------------------------------------------------------------
                */ else {

                    ReservationRoom::create([
                        'reservation_id' =>
                            $reservation->id,

                        'room_type_id' =>
                            $roomType->id,

                        'rate_plan_id' =>
                            $ratePlan?->id,

                        'room_id' =>
                            null,

                        'check_in' =>
                            $validated['check_in'],

                        'check_out' =>
                            $validated['check_out'],

                        'adults' =>
                            $validated['adults'],

                        'children' =>
                            $validated['children'],

                        'nightly_rate' =>
                            $nightlyRate,

                        'total_amount' =>
                            $roomTotal,
                    ]);
                }


                return array_values(
                    array_unique(
                        $inventoryIds
                    )
                );
            }
        );


        /*
        |--------------------------------------------------------------------------
        | Sync Inventory To Channex
        |--------------------------------------------------------------------------
        */
        $syncResult =
            $inventoryService
                ->syncToChannex(
                    $property,
                    $inventoryIds,
                    $channex
                );


        /*
        |--------------------------------------------------------------------------
        | Failed / Warning
        |--------------------------------------------------------------------------
        */
        if (
            in_array(
                $syncResult['status'] ?? '',
                [
                    'failed',
                    'warning',
                ],
                true
            )
        ) {

            return redirect()
                ->route(
                    'pms.reservations.show',
                    $reservation
                )
                ->with(
                    'success',
                    'Reservation updated successfully.'
                )
                ->with(
                    'error',
                    $syncResult['message']
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Success
        |--------------------------------------------------------------------------
        */
        return redirect()
            ->route(
                'pms.reservations.show',
                $reservation
            )
            ->with(
                'success',
                'Reservation updated successfully. '
                . (
                    $syncResult['message']
                    ?? ''
                )
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Cancel Reservation
    |--------------------------------------------------------------------------
    */
    public function destroy(
        Reservation $reservation,
        ReservationInventoryService $inventoryService,
        ChannexService $channex
    ) {
        $property = Property::firstOrFail();


        abort_if(
            $reservation->property_id
            !==
            $property->id,
            404
        );


        /*
        |--------------------------------------------------------------------------
        | Already Cancelled
        |--------------------------------------------------------------------------
        */
        if (
            $reservation->status
            ===
            'cancelled'
        ) {

            return redirect()
                ->route(
                    'pms.reservations.index'
                )
                ->with(
                    'success',
                    'Reservation is already cancelled.'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Checked Out Cannot Cancel
        |--------------------------------------------------------------------------
        */
        if (
            $reservation->status
            ===
            'checked_out'
        ) {

            return redirect()
                ->route(
                    'pms.reservations.show',
                    $reservation
                )
                ->with(
                    'error',
                    'Checked-out reservation cannot be cancelled.'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Room Line
        |--------------------------------------------------------------------------
        */
        $reservationRoom =
            $reservation
                ->rooms()
                ->with(
                    'roomType'
                )
                ->first();


        /*
        |--------------------------------------------------------------------------
        | Should Release Inventory?
        |--------------------------------------------------------------------------
        */
        $shouldRelease =
            $reservationRoom
            &&
            $reservationRoom->roomType
            &&
            $inventoryService
                ->holdsInventory(
                    $reservation->status
                );


        /*
        |--------------------------------------------------------------------------
        | Cancel Transaction
        |--------------------------------------------------------------------------
        */
        $inventoryIds =
            DB::transaction(
                function () use ($reservation, $reservationRoom, $property, $inventoryService, $shouldRelease) {

                    $inventoryIds = [];


                    /*
                    |--------------------------------------------------------------------------
                    | Release Inventory
                    |--------------------------------------------------------------------------
                    */
                    if ($shouldRelease) {

                        $inventoryIds =
                            $inventoryService
                                ->release(
                                    $property,
                                    $reservationRoom->roomType,
                                    $reservationRoom->check_in,
                                    $reservationRoom->check_out
                                );
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Cancel Reservation
                    |--------------------------------------------------------------------------
                    */
                    $reservation->update([
                        'status' =>
                            'cancelled',

                        'cancelled_at' =>
                            now(),
                    ]);


                    return $inventoryIds;
                }
            );


        /*
        |--------------------------------------------------------------------------
        | Sync Returned Inventory → Channex
        |--------------------------------------------------------------------------
        */
        $syncResult =
            $inventoryService
                ->syncToChannex(
                    $property,
                    $inventoryIds,
                    $channex
                );


        /*
        |--------------------------------------------------------------------------
        | Failed / Warning
        |--------------------------------------------------------------------------
        */
        if (
            in_array(
                $syncResult['status'] ?? '',
                [
                    'failed',
                    'warning',
                ],
                true
            )
        ) {

            return redirect()
                ->route(
                    'pms.reservations.index'
                )
                ->with(
                    'success',
                    'Reservation cancelled successfully.'
                )
                ->with(
                    'error',
                    $syncResult['message']
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Success
        |--------------------------------------------------------------------------
        */
        return redirect()
            ->route(
                'pms.reservations.index'
            )
            ->with(
                'success',
                'Reservation cancelled successfully. '
                . (
                    $syncResult['message']
                    ?? ''
                )
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Validate Reservation
    |--------------------------------------------------------------------------
    */
    private function validateReservation(
        Request $request,
        Property $property
    ): array {

        /*
        |--------------------------------------------------------------------------
        | Valid Guests
        |--------------------------------------------------------------------------
        */
        $guestIds = Guest::where(
            'property_id',
            $property->id
        )
            ->pluck('id')
            ->all();


        /*
        |--------------------------------------------------------------------------
        | Valid Room Types
        |--------------------------------------------------------------------------
        */
        $roomTypeIds = RoomType::where(
            'property_id',
            $property->id
        )
            ->pluck('id')
            ->all();


        /*
        |--------------------------------------------------------------------------
        | Valid Rate Plans
        |--------------------------------------------------------------------------
        */
        $ratePlanIds = RatePlan::where(
            'property_id',
            $property->id
        )
            ->pluck('id')
            ->all();


        return $request->validate([

            /*
            |--------------------------------------------------------------------------
            | Guest
            |--------------------------------------------------------------------------
            */
            'guest_id' => [
                'required',
                'integer',

                Rule::in(
                    $guestIds
                ),
            ],


            /*
            |--------------------------------------------------------------------------
            | Room Type
            |--------------------------------------------------------------------------
            */
            'room_type_id' => [
                'required',
                'integer',

                Rule::in(
                    $roomTypeIds
                ),
            ],


            /*
            |--------------------------------------------------------------------------
            | Rate Plan
            |--------------------------------------------------------------------------
            */
            'rate_plan_id' => [
                'nullable',
                'integer',

                Rule::in(
                    $ratePlanIds
                ),
            ],


            /*
            |--------------------------------------------------------------------------
            | Stay Dates
            |--------------------------------------------------------------------------
            */
            'check_in' => [
                'required',
                'date',
            ],

            'check_out' => [
                'required',
                'date',
                'after:check_in',
            ],


            /*
            |--------------------------------------------------------------------------
            | Occupancy
            |--------------------------------------------------------------------------
            */
            'adults' => [
                'required',
                'integer',
                'min:1',
            ],

            'children' => [
                'required',
                'integer',
                'min:0',
            ],


            /*
            |--------------------------------------------------------------------------
            | Rate
            |--------------------------------------------------------------------------
            */
            'nightly_rate' => [
                'nullable',
                'numeric',
                'min:0',
            ],


            /*
            |--------------------------------------------------------------------------
            | Tax
            |--------------------------------------------------------------------------
            */
            'tax_amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],


            /*
            |--------------------------------------------------------------------------
            | Fee
            |--------------------------------------------------------------------------
            */
            'fee_amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],


            /*
            |--------------------------------------------------------------------------
            | Booking Source
            |--------------------------------------------------------------------------
            */
            'source' => [
                'required',

                Rule::in([
                    'direct',
                    'website',
                    'walk_in',
                    'phone',
                    'channex',
                    'ota',
                ]),
            ],


            /*
            |--------------------------------------------------------------------------
            | Channel
            |--------------------------------------------------------------------------
            */
            'channel' => [
                'nullable',
                'string',
                'max:100',
            ],


            /*
            |--------------------------------------------------------------------------
            | External Reservation ID
            |--------------------------------------------------------------------------
            */
            'external_reservation_id' => [
                'nullable',
                'string',
                'max:255',
            ],


            /*
            |--------------------------------------------------------------------------
            | Reservation Status
            |--------------------------------------------------------------------------
            */
            'status' => [
                'required',

                Rule::in([
                    'pending',
                    'confirmed',
                    'checked_in',
                    'checked_out',
                    'cancelled',
                    'no_show',
                ]),
            ],


            /*
            |--------------------------------------------------------------------------
            | Special Requests
            |--------------------------------------------------------------------------
            */
            'special_requests' => [
                'nullable',
                'string',
                'max:2000',
            ],


            /*
            |--------------------------------------------------------------------------
            | Notes
            |--------------------------------------------------------------------------
            */
            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Validate Occupancy
    |--------------------------------------------------------------------------
    */
    private function validateOccupancy(
        RoomType $roomType,
        int $adults,
        int $children
    ): void {

        /*
        |--------------------------------------------------------------------------
        | Adults
        |--------------------------------------------------------------------------
        */
        if (
            $adults
            >
            (int) $roomType->max_adults
        ) {

            throw ValidationException::withMessages([
                'adults' =>
                    "Maximum adults for {$roomType->name} is {$roomType->max_adults}.",
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Children
        |--------------------------------------------------------------------------
        */
        if (
            $children
            >
            (int) $roomType->max_children
        ) {

            throw ValidationException::withMessages([
                'children' =>
                    "Maximum children for {$roomType->name} is {$roomType->max_children}.",
            ]);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Generate Reservation Code
    |--------------------------------------------------------------------------
    */
    private function generateReservationCode(
        Property $property
    ): string {

        $latestReservation =
            Reservation::where(
                'property_id',
                $property->id
            )
                ->where(
                    'code',
                    'like',
                    'RES_%'
                )
                ->orderByDesc('id')
                ->first();


        /*
        |--------------------------------------------------------------------------
        | Default
        |--------------------------------------------------------------------------
        */
        $nextNumber =
            1;


        /*
        |--------------------------------------------------------------------------
        | Existing Reservation
        |--------------------------------------------------------------------------
        */
        if ($latestReservation) {

            $number =
                (int) str_replace(
                    'RES_',
                    '',
                    $latestReservation->code
                );


            $nextNumber =
                $number + 1;
        }


        /*
        |--------------------------------------------------------------------------
        | RES_000001
        |--------------------------------------------------------------------------
        */
        return 'RES_'
            . str_pad(
                (string) $nextNumber,
                6,
                '0',
                STR_PAD_LEFT
            );
    }
}