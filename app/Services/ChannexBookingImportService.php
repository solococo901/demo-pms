<?php

namespace App\Services;

use App\Models\ChannexBookingRevision;
use App\Models\Guest;
use App\Models\Inventory;
use App\Models\Property;
use App\Models\RatePlan;
use App\Models\Reservation;
use App\Models\ReservationRoom;
use App\Models\RoomType;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class ChannexBookingImportService
{
    public function __construct(
        private readonly ChannexBookingApiService $bookingApi,
        private readonly ReservationInventoryService $inventoryService,
        private readonly ChannexService $channexService
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Pull All Unacknowledged Revisions
    |--------------------------------------------------------------------------
    |
    | STEP 20.1 only imports revisions with status = new.
    |
    | Modified and cancelled revisions are intentionally left unacknowledged
    | until Step 20.2 adds their business logic.
    |
    */
    public function pullNewBookings(
        ?int $limit = null
    ): array {
        $feed = $this->bookingApi
            ->getRevisionFeed();

        /*
        |--------------------------------------------------------------------------
        | Optional Test Limit
        |--------------------------------------------------------------------------
        |
        | Useful on staging so we can safely process one revision first.
        |
        */
        if (
            $limit !== null
            &&
            $limit > 0
        ) {
            $feed =
                array_slice(
                    $feed,
                    0,
                    $limit
                );
        }

        $summary = [
            'received' => count($feed),
            'imported' => 0,
            'acknowledged' => 0,
            'skipped' => 0,
            'failed' => 0,
            'messages' => [],
        ];

        foreach ($feed as $resource) {
            try {
                $normalized = $this->normalizeRevision(
                    $resource
                );

                if (
                    $normalized['status']
                    !==
                    'new'
                ) {
                    $summary['skipped']++;

                    $summary['messages'][] =
                        "Revision {$normalized['revision_id']} skipped: "
                        . "status {$normalized['status']} will be handled in Step 20.2.";

                    continue;
                }

                $result = $this->processNewRevision(
                    $resource
                );

                if ($result['imported']) {
                    $summary['imported']++;
                }

                if ($result['acknowledged']) {
                    $summary['acknowledged']++;
                }

                $summary['messages'][] =
                    $result['message'];

            } catch (Throwable $exception) {
                $summary['failed']++;

                $summary['messages'][] =
                    $exception->getMessage();
            }
        }

        return $summary;
    }

    /*
    |--------------------------------------------------------------------------
    | Pull One Revision By ID
    |--------------------------------------------------------------------------
    |
    | Useful while testing a known booking in Channex UI.
    | It does not depend on the Feed returning that revision.
    |
    */
    public function pullRevisionById(
        string $revisionId
    ): array {
        $summary = [
            'received' => 0,
            'imported' => 0,
            'acknowledged' => 0,
            'skipped' => 0,
            'failed' => 0,
            'messages' => [],
        ];

        try {
            $resource =
                $this->bookingApi
                    ->getRevision(
                        $revisionId
                    );

            $summary['received'] = 1;

            $normalized =
                $this->normalizeRevision(
                    $resource
                );

            if (
                $normalized['status']
                !==
                'new'
            ) {
                $summary['skipped'] = 1;

                $summary['messages'][] =
                    "Revision {$normalized['revision_id']} has status "
                    . "{$normalized['status']}. Step 20.1 only imports new bookings.";

                return $summary;
            }

            $result =
                $this->processNewRevision(
                    $resource
                );

            if (
                $result['imported']
                ?? false
            ) {
                $summary['imported'] = 1;
            }

            if (
                $result['acknowledged']
                ?? false
            ) {
                $summary['acknowledged'] = 1;
            }

            $summary['messages'][] =
                $result['message']
                ?? "Revision {$revisionId} processed.";

        } catch (Throwable $exception) {
            $summary['failed'] = 1;

            $summary['messages'][] =
                $exception->getMessage();
        }

        return $summary;
    }


    /*
    |--------------------------------------------------------------------------
    | Process One "new" Revision
    |--------------------------------------------------------------------------
    */
    public function processNewRevision(
        array $resource
    ): array {
        $revision = $this->normalizeRevision(
            $resource
        );

        if (
            $revision['status']
            !==
            'new'
        ) {
            throw new RuntimeException(
                "Revision {$revision['revision_id']} is not a new booking."
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Property Mapping
        |--------------------------------------------------------------------------
        */
        $property =
            Property::where(
                'channex_property_id',
                $revision['property_id']
            )->first();

        if (!$property) {
            $this->saveFailedRevision(
                $revision,
                null,
                'No PMS Property is mapped to Channex property '
                . $revision['property_id']
                . '.'
            );

            throw new RuntimeException(
                'Channex booking cannot be imported: Property mapping was not found.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Revision Audit / Idempotency
        |--------------------------------------------------------------------------
        */
        $revisionLog =
            ChannexBookingRevision::firstOrCreate(
                [
                    'revision_id' =>
                        $revision['revision_id'],
                ],
                [
                    'property_id' =>
                        $property->id,

                    'channex_booking_id' =>
                        $revision['booking_id'],

                    'system_id' =>
                        $revision['system_id'],

                    'revision_status' =>
                        $revision['status'],

                    'ota_reservation_code' =>
                        $revision['ota_reservation_code'],

                    'ota_name' =>
                        $revision['ota_name'],

                    'processing_status' =>
                        'received',

                    'payload' =>
                        $resource,

                    'received_at' =>
                        $revision['inserted_at'],
                ]
            );

        /*
        |--------------------------------------------------------------------------
        | Already Acknowledged
        |--------------------------------------------------------------------------
        */
        if ($revisionLog->acknowledged_at) {
            return [
                'imported' => false,
                'acknowledged' => true,
                'message' =>
                    "Revision {$revision['revision_id']} was already acknowledged.",
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Already Processed, Ack Retry Only
        |--------------------------------------------------------------------------
        |
        | This protects against duplicate reservations if the previous ACK failed.
        |
        */
        if (
            $revisionLog->processing_status
            ===
            'processed'
            &&
            $revisionLog->reservation_id
        ) {
            $this->acknowledgeRevision(
                $revisionLog
            );

            return [
                'imported' => false,
                'acknowledged' => true,
                'message' =>
                    "Revision {$revision['revision_id']} was already saved; ACK retried successfully.",
            ];
        }

        try {
            /*
            |--------------------------------------------------------------------------
            | Import Inside DB Transaction
            |--------------------------------------------------------------------------
            */
            $reservation =
                DB::transaction(
                    function () use (
                        $property,
                        $revision,
                        $revisionLog
                    ) {
                        /*
                        |--------------------------------------------------------------------------
                        | Booking-Level Idempotency
                        |--------------------------------------------------------------------------
                        |
                        | If Channex retries the same booking with another delivery attempt,
                        | do not create a duplicate Reservation.
                        |
                        */
                        $existing =
                            Reservation::where(
                                'property_id',
                                $property->id
                            )
                                ->where(
                                    'channex_booking_id',
                                    $revision['booking_id']
                                )
                                ->first();

                        if ($existing) {
                            $revisionLog->update([
                                'reservation_id' =>
                                    $existing->id,

                                'processing_status' =>
                                    'processed',

                                'processed_at' =>
                                    now(),

                                'error_message' =>
                                    null,
                            ]);

                            return $existing;
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Validate Rooms And Mapping Before Creating Anything
                        |--------------------------------------------------------------------------
                        */
                        if (
                            empty($revision['rooms'])
                        ) {
                            throw new RuntimeException(
                                'Channex booking does not contain any rooms.'
                            );
                        }

                        $roomMappings = [];

                        foreach (
                            $revision['rooms']
                            as $index => $bookingRoom
                        ) {
                            $channexRoomTypeId =
                                data_get(
                                    $bookingRoom,
                                    'room_type_id'
                                );

                            if (!$channexRoomTypeId) {
                                throw new RuntimeException(
                                    'Booking room #'
                                    . ($index + 1)
                                    . ' has no mapped Channex room_type_id.'
                                );
                            }

                            $roomType =
                                RoomType::where(
                                    'property_id',
                                    $property->id
                                )
                                    ->where(
                                        'channex_room_type_id',
                                        $channexRoomTypeId
                                    )
                                    ->first();

                            if (!$roomType) {
                                throw new RuntimeException(
                                    'No PMS Room Type mapping found for Channex room_type_id '
                                    . $channexRoomTypeId
                                    . '.'
                                );
                            }

                            $channexRatePlanId =
                                data_get(
                                    $bookingRoom,
                                    'rate_plan_id'
                                )
                                ?: data_get(
                                    $bookingRoom,
                                    'meta.parent_rate_plan_id'
                                );

                            $ratePlan = null;

                            if ($channexRatePlanId) {
                                $ratePlan =
                                    RatePlan::where(
                                        'property_id',
                                        $property->id
                                    )
                                        ->where(
                                            'room_type_id',
                                            $roomType->id
                                        )
                                        ->where(
                                            'channex_rate_plan_id',
                                            $channexRatePlanId
                                        )
                                        ->first();
                            }

                            $roomMappings[] = [
                                'payload' =>
                                    $bookingRoom,

                                'room_type' =>
                                    $roomType,

                                'rate_plan' =>
                                    $ratePlan,
                            ];
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Guest
                        |--------------------------------------------------------------------------
                        */
                        $guest =
                            $this->resolveGuest(
                                $property,
                                $revision
                            );

                        /*
                        |--------------------------------------------------------------------------
                        | Amounts
                        |--------------------------------------------------------------------------
                        */
                        $reservationTotal =
                            max(
                                0,
                                (float) $revision['amount']
                            );

                        $roomSubtotal = 0.0;

                        foreach (
                            $revision['rooms']
                            as $bookingRoom
                        ) {
                            $roomSubtotal +=
                                max(
                                    0,
                                    (float) data_get(
                                        $bookingRoom,
                                        'amount',
                                        0
                                    )
                                );
                        }

                        if ($roomSubtotal <= 0) {
                            $roomSubtotal =
                                $reservationTotal;
                        }

                        $otherCharges =
                            max(
                                0,
                                $reservationTotal
                                -
                                $roomSubtotal
                            );

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
                                    $guest?->id,

                                'code' =>
                                    $this->generateReservationCode(
                                        $property
                                    ),

                                'source' =>
                                    'channex',

                                'channel' =>
                                    $revision['ota_name']
                                    ?: 'Channex',

                                'external_reservation_id' =>
                                    $revision['ota_reservation_code']
                                    ?: $revision['unique_id'],

                                'channex_booking_id' =>
                                    $revision['booking_id'],

                                'status' =>
                                    'confirmed',

                                /*
                                |--------------------------------------------------------------------------
                                | IMPORTANT
                                |--------------------------------------------------------------------------
                                |
                                | payment_collect = ota does NOT automatically mean that the
                                | PMS has actually received money, so Phase 1 does not create
                                | a Payment automatically.
                                |
                                */
                                'payment_status' =>
                                    'unpaid',

                                'currency' =>
                                    $revision['currency']
                                    ?: $property->currency
                                    ?: 'VND',

                                'subtotal' =>
                                    $roomSubtotal,

                                'tax_amount' =>
                                    0,

                                'fee_amount' =>
                                    $otherCharges,

                                'total_amount' =>
                                    $reservationTotal,

                                'paid_amount' =>
                                    0,

                                'special_requests' =>
                                    $revision['notes'],

                                'notes' =>
                                    $this->buildInternalNotes(
                                        $revision
                                    ),

                                'booked_at' =>
                                    $revision['inserted_at']
                                    ?: now(),
                            ]);

                        /*
                        |--------------------------------------------------------------------------
                        | Reservation Rooms + Inventory
                        |--------------------------------------------------------------------------
                        */
                        foreach (
                            $roomMappings
                            as $mapping
                        ) {
                            $bookingRoom =
                                $mapping['payload'];

                            /** @var RoomType $roomType */
                            $roomType =
                                $mapping['room_type'];

                            /** @var RatePlan|null $ratePlan */
                            $ratePlan =
                                $mapping['rate_plan'];

                            $checkIn =
                                data_get(
                                    $bookingRoom,
                                    'checkin_date'
                                )
                                ?: $revision['arrival_date'];

                            $checkOut =
                                data_get(
                                    $bookingRoom,
                                    'checkout_date'
                                )
                                ?: $revision['departure_date'];

                            if (
                                !$checkIn
                                ||
                                !$checkOut
                            ) {
                                throw new RuntimeException(
                                    'Channex booking room is missing check-in/check-out date.'
                                );
                            }

                            $nights =
                                max(
                                    1,
                                    CarbonImmutable::parse(
                                        $checkIn
                                    )->diffInDays(
                                        CarbonImmutable::parse(
                                            $checkOut
                                        )
                                    )
                                );

                            $roomAmount =
                                max(
                                    0,
                                    (float) data_get(
                                        $bookingRoom,
                                        'amount',
                                        0
                                    )
                                );

                            $nightlyRate =
                                $roomAmount > 0
                                    ? $roomAmount / $nights
                                    : (
                                        $ratePlan
                                            ? (float) $ratePlan->base_rate
                                            : (float) $roomType->base_price
                                    );

                            $adults =
                                max(
                                    1,
                                    (int) data_get(
                                        $bookingRoom,
                                        'occupancy.adults',
                                        1
                                    )
                                );

                            $children =
                                max(
                                    0,
                                    (int) data_get(
                                        $bookingRoom,
                                        'occupancy.children',
                                        0
                                    )
                                );

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
                                    $checkIn,

                                'check_out' =>
                                    $checkOut,

                                'adults' =>
                                    $adults,

                                'children' =>
                                    $children,

                                'nightly_rate' =>
                                    $nightlyRate,

                                'total_amount' =>
                                    $roomAmount,

                                'notes' =>
                                    data_get(
                                        $bookingRoom,
                                        'ota_unique_id'
                                    )
                                        ? 'OTA Room ID: '
                                            . data_get(
                                                $bookingRoom,
                                                'ota_unique_id'
                                            )
                                        : null,
                            ]);

                            /*
                            |--------------------------------------------------------------------------
                            | Deduct PMS Inventory
                            |--------------------------------------------------------------------------
                            */
                            $this->inventoryService->reserve(
                                $property,
                                $roomType,
                                $checkIn,
                                $checkOut
                            );
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Revision Processed
                        |--------------------------------------------------------------------------
                        */
                        $revisionLog->update([
                            'reservation_id' =>
                                $reservation->id,

                            'processing_status' =>
                                'processed',

                            'processed_at' =>
                                now(),

                            'error_message' =>
                                null,
                        ]);

                        return $reservation;
                    }
                );

            /*
            |--------------------------------------------------------------------------
            | Sync PMS Availability Back To Channex
            |--------------------------------------------------------------------------
            |
            | Channex already handles OTA-side availability changes, but PMS should
            | publish its current calculated availability after saving the booking
            | so both systems converge on the same state.
            |
            | A sync warning must NOT create a duplicate booking.
            |
            */
            $this->syncReservationInventory(
                $property,
                $reservation
            );

            /*
            |--------------------------------------------------------------------------
            | ACK Only After Durable PMS Save
            |--------------------------------------------------------------------------
            */
            $this->acknowledgeRevision(
                $revisionLog
            );

            return [
                'imported' => true,
                'acknowledged' => true,
                'message' =>
                    'Imported '
                    . ($revision['ota_name'] ?: 'OTA')
                    . ' booking '
                    . ($revision['ota_reservation_code'] ?: $revision['booking_id'])
                    . ' as '
                    . $reservation->code
                    . '.',
            ];

        } catch (Throwable $exception) {
            $revisionLog->update([
                'processing_status' =>
                    'error',

                'error_message' =>
                    $exception->getMessage(),
            ]);

            throw new RuntimeException(
                'Revision '
                . $revision['revision_id']
                . ' failed: '
                . $exception->getMessage(),
                previous: $exception
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Acknowledge
    |--------------------------------------------------------------------------
    */
    private function acknowledgeRevision(
        ChannexBookingRevision $revisionLog
    ): void {
        $this->bookingApi->acknowledge(
            $revisionLog->revision_id
        );

        $revisionLog->update([
            'processing_status' =>
                'acknowledged',

            'acknowledged_at' =>
                now(),

            'error_message' =>
                null,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Sync Inventory Related To Imported Reservation
    |--------------------------------------------------------------------------
    */
    private function syncReservationInventory(
        Property $property,
        Reservation $reservation
    ): void {
        $reservation->loadMissing(
            'rooms.roomType'
        );

        $inventoryIds = [];

        foreach (
            $reservation->rooms
            as $roomLine
        ) {
            if (!$roomLine->room_type_id) {
                continue;
            }

            $ids =
                Inventory::where(
                    'property_id',
                    $property->id
                )
                    ->where(
                        'room_type_id',
                        $roomLine->room_type_id
                    )
                    ->where(
                        'date',
                        '>=',
                        $roomLine->check_in
                    )
                    ->where(
                        'date',
                        '<',
                        $roomLine->check_out
                    )
                    ->pluck('id')
                    ->all();

            $inventoryIds =
                array_merge(
                    $inventoryIds,
                    $ids
                );
        }

        $inventoryIds =
            array_values(
                array_unique(
                    $inventoryIds
                )
            );

        if (empty($inventoryIds)) {
            return;
        }

        try {
            $this->inventoryService
                ->syncToChannex(
                    $property,
                    $inventoryIds,
                    $this->channexService
                );

        } catch (Throwable $exception) {
            /*
            |--------------------------------------------------------------------------
            | Do Not Roll Back Booking Import
            |--------------------------------------------------------------------------
            |
            | The booking is already safely stored. ARI can be retried separately.
            |
            */
            report($exception);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Guest Resolution
    |--------------------------------------------------------------------------
    */
    private function resolveGuest(
        Property $property,
        array $revision
    ): ?Guest {
        $customer =
            $revision['customer'];

        if (!is_array($customer)) {
            $customer = [];
        }

        $email =
            trim(
                (string) data_get(
                    $customer,
                    'mail',
                    ''
                )
            );

        $phone =
            trim(
                (string) data_get(
                    $customer,
                    'phone',
                    ''
                )
            );

        /*
        |--------------------------------------------------------------------------
        | Reuse Existing Guest
        |--------------------------------------------------------------------------
        */
        if ($email !== '') {
            $guest =
                Guest::where(
                    'property_id',
                    $property->id
                )
                    ->where(
                        'email',
                        $email
                    )
                    ->first();

            if ($guest) {
                return $guest;
            }
        }

        if ($phone !== '') {
            $guest =
                Guest::where(
                    'property_id',
                    $property->id
                )
                    ->where(
                        'phone',
                        $phone
                    )
                    ->first();

            if ($guest) {
                return $guest;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Fallback To First Room Guest Name
        |--------------------------------------------------------------------------
        */
        $firstRoomGuest =
            data_get(
                $revision,
                'rooms.0.guests.0',
                []
            );

        $firstName =
            trim(
                (string) (
                    data_get(
                        $customer,
                        'name'
                    )
                    ?: data_get(
                        $firstRoomGuest,
                        'name'
                    )
                    ?: 'OTA Guest'
                )
            );

        $lastName =
            trim(
                (string) (
                    data_get(
                        $customer,
                        'surname'
                    )
                    ?: data_get(
                        $firstRoomGuest,
                        'surname'
                    )
                    ?: ''
                )
            );

        $addressParts =
            array_filter([
                data_get(
                    $customer,
                    'address'
                ),
                data_get(
                    $customer,
                    'city'
                ),
                data_get(
                    $customer,
                    'zip'
                ),
            ]);

        return Guest::create([
            'property_id' =>
                $property->id,

            'code' =>
                $this->generateGuestCode(
                    $property
                ),

            'first_name' =>
                $firstName,

            'last_name' =>
                $lastName !== ''
                    ? $lastName
                    : null,

            'email' =>
                $email !== ''
                    ? $email
                    : null,

            'phone' =>
                $phone !== ''
                    ? $phone
                    : null,

            'nationality' =>
                data_get(
                    $customer,
                    'country'
                ),

            'address' =>
                !empty($addressParts)
                    ? implode(
                        ', ',
                        $addressParts
                    )
                    : null,

            'notes' =>
                'Created automatically from Channex booking.',

            'status' =>
                'active',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Normalize JSON:API Revision
    |--------------------------------------------------------------------------
    */
    private function normalizeRevision(
        array $resource
    ): array {
        $attributes =
            data_get(
                $resource,
                'attributes',
                []
            );

        if (!is_array($attributes)) {
            $attributes = [];
        }

        $revisionId =
            (string) (
                data_get(
                    $resource,
                    'id'
                )
                ?: data_get(
                    $attributes,
                    'id'
                )
            );

        if ($revisionId === '') {
            throw new RuntimeException(
                'Channex booking revision has no revision ID.'
            );
        }

        return [
            'revision_id' =>
                $revisionId,

            'property_id' =>
                (string) data_get(
                    $attributes,
                    'property_id',
                    ''
                ),

            'booking_id' =>
                (string) data_get(
                    $attributes,
                    'booking_id',
                    ''
                ),

            'unique_id' =>
                data_get(
                    $attributes,
                    'unique_id'
                ),

            'system_id' =>
                data_get(
                    $attributes,
                    'system_id'
                ),

            'ota_reservation_code' =>
                data_get(
                    $attributes,
                    'ota_reservation_code'
                ),

            'ota_name' =>
                data_get(
                    $attributes,
                    'ota_name'
                ),

            'status' =>
                (string) data_get(
                    $attributes,
                    'status',
                    ''
                ),

            'rooms' =>
                is_array(
                    data_get(
                        $attributes,
                        'rooms'
                    )
                )
                    ? data_get(
                        $attributes,
                        'rooms'
                    )
                    : [],

            'customer' =>
                data_get(
                    $attributes,
                    'customer',
                    []
                ),

            'arrival_date' =>
                data_get(
                    $attributes,
                    'arrival_date'
                ),

            'departure_date' =>
                data_get(
                    $attributes,
                    'departure_date'
                ),

            'amount' =>
                (float) data_get(
                    $attributes,
                    'amount',
                    0
                ),

            'currency' =>
                data_get(
                    $attributes,
                    'currency'
                ),

            'notes' =>
                data_get(
                    $attributes,
                    'notes'
                ),

            'payment_collect' =>
                data_get(
                    $attributes,
                    'payment_collect'
                ),

            'payment_type' =>
                data_get(
                    $attributes,
                    'payment_type'
                ),

            'inserted_at' =>
                data_get(
                    $attributes,
                    'inserted_at'
                ),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Internal Notes
    |--------------------------------------------------------------------------
    */
    private function buildInternalNotes(
        array $revision
    ): string {
        $notes = [
            'Imported from Channex.',
        ];

        if ($revision['ota_name']) {
            $notes[] =
                'OTA: '
                . $revision['ota_name']
                . '.';
        }

        if ($revision['payment_collect']) {
            $notes[] =
                'Payment collect: '
                . $revision['payment_collect']
                . '.';
        }

        if ($revision['payment_type']) {
            $notes[] =
                'Payment type: '
                . $revision['payment_type']
                . '.';
        }

        return implode(
            ' ',
            $notes
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Reservation Code
    |--------------------------------------------------------------------------
    */
    private function generateReservationCode(
        Property $property
    ): string {
        $latest =
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

        $next =
            $latest
                ? (
                    (int) str_replace(
                        'RES_',
                        '',
                        $latest->code
                    )
                ) + 1
                : 1;

        return 'RES_'
            . str_pad(
                (string) $next,
                6,
                '0',
                STR_PAD_LEFT
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Guest Code
    |--------------------------------------------------------------------------
    */
    private function generateGuestCode(
        Property $property
    ): string {
        $latest =
            Guest::where(
                'property_id',
                $property->id
            )
                ->where(
                    'code',
                    'like',
                    'GST_%'
                )
                ->orderByDesc('id')
                ->first();

        $next =
            $latest
                ? (
                    (int) str_replace(
                        'GST_',
                        '',
                        $latest->code
                    )
                ) + 1
                : 1;

        return 'GST_'
            . str_pad(
                (string) $next,
                6,
                '0',
                STR_PAD_LEFT
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Save Mapping Failure Even Before Property Is Resolved
    |--------------------------------------------------------------------------
    */
    private function saveFailedRevision(
        array $revision,
        ?Property $property,
        string $message
    ): void {
        ChannexBookingRevision::updateOrCreate(
            [
                'revision_id' =>
                    $revision['revision_id'],
            ],
            [
                'property_id' =>
                    $property?->id,

                'channex_booking_id' =>
                    $revision['booking_id'],

                'system_id' =>
                    $revision['system_id'],

                'revision_status' =>
                    $revision['status'],

                'ota_reservation_code' =>
                    $revision['ota_reservation_code'],

                'ota_name' =>
                    $revision['ota_name'],

                'processing_status' =>
                    'error',

                'payload' =>
                    $revision,

                'error_message' =>
                    $message,

                'received_at' =>
                    $revision['inserted_at'],
            ]
        );
    }
}
