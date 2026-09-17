<?php

namespace App\Http\Controllers\Pms;

use App\Http\Controllers\Controller;
use App\Models\FolioItem;
use App\Models\Payment;
use App\Models\PaymentRefund;
use App\Models\Property;
use App\Models\Reservation;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FolioController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Add Folio Item
    |--------------------------------------------------------------------------
    */
    public function store(
        Request $request,
        Reservation $reservation
    ) {
        /*
        |--------------------------------------------------------------------------
        | Property
        |--------------------------------------------------------------------------
        */
        $property =
            Property::firstOrFail();


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
        | Reservation Status
        |--------------------------------------------------------------------------
        */
        if (
            in_array(
                $reservation->status,
                [
                    'cancelled',
                    'no_show',
                ],
                true
            )
        ) {

            return back()->with(
                'error',
                'Cannot add charges to a cancelled or no-show reservation.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Validate
        |--------------------------------------------------------------------------
        */
        $validated =
            $request->validate([
                'category' => [
                    'required',

                    Rule::in([
                        'minibar',
                        'laundry',
                        'airport_transfer',
                        'extra_bed',
                        'late_checkout',
                        'damage_fee',
                        'other',
                    ]),
                ],

                'description' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'quantity' => [
                    'required',
                    'integer',
                    'min:1',
                    'max:100',
                ],

                'unit_price' => [
                    'required',
                    'numeric',
                    'min:0',
                ],

                'posted_at' => [
                    'nullable',
                    'date',
                ],

                'notes' => [
                    'nullable',
                    'string',
                    'max:2000',
                ],
            ]);


        /*
        |--------------------------------------------------------------------------
        | Timezone
        |--------------------------------------------------------------------------
        */
        $timezone =
            $property->timezone
            ?? 'Asia/Ho_Chi_Minh';


        /*
        |--------------------------------------------------------------------------
        | Transaction
        |--------------------------------------------------------------------------
        */
        $folioItem =
            DB::transaction(
                function () use (
                    $property,
                    $reservation,
                    $validated,
                    $timezone
                ) {

                    /*
                    |--------------------------------------------------------------------------
                    | Lock Reservation
                    |--------------------------------------------------------------------------
                    */
                    $lockedReservation =
                        Reservation::whereKey(
                            $reservation->id
                        )
                            ->lockForUpdate()
                            ->firstOrFail();


                    /*
                    |--------------------------------------------------------------------------
                    | Calculate Total
                    |--------------------------------------------------------------------------
                    */
                    $quantity =
                        (int) $validated[
                            'quantity'
                        ];


                    $unitPrice =
                        (float) $validated[
                            'unit_price'
                        ];


                    $total =
                        $quantity
                        *
                        $unitPrice;


                    /*
                    |--------------------------------------------------------------------------
                    | Posted At
                    |--------------------------------------------------------------------------
                    */
                    $postedAt =
                        !empty(
                            $validated[
                                'posted_at'
                            ]
                        )
                            ? CarbonImmutable::parse(
                                $validated[
                                    'posted_at'
                                ],
                                $timezone
                            )
                            : CarbonImmutable::now(
                                $timezone
                            );


                    /*
                    |--------------------------------------------------------------------------
                    | Create Folio Item
                    |--------------------------------------------------------------------------
                    */
                    $folioItem =
                        FolioItem::create([
                            'property_id' =>
                                $property->id,

                            'reservation_id' =>
                                $lockedReservation->id,

                            'code' =>
                                $this->generateFolioCode(
                                    $property
                                ),

                            'category' =>
                                $validated[
                                    'category'
                                ],

                            'description' =>
                                $validated[
                                    'description'
                                ],

                            'quantity' =>
                                $quantity,

                            'unit_price' =>
                                $unitPrice,

                            'total_amount' =>
                                $total,

                            'currency' =>
                                $lockedReservation
                                    ->currency
                                ?? $property->currency
                                ?? 'VND',

                            'status' =>
                                'active',

                            'posted_at' =>
                                $postedAt,

                            'notes' =>
                                $validated[
                                    'notes'
                                ]
                                ?? null,
                        ]);


                    /*
                    |--------------------------------------------------------------------------
                    | Refresh Reservation Financials
                    |--------------------------------------------------------------------------
                    */
                    $this->refreshReservationFinancials(
                        $lockedReservation
                    );


                    return $folioItem;
                }
            );


        return redirect()
            ->route(
                'pms.reservations.show',
                $reservation
            )
            ->with(
                'success',
                "Charge {$folioItem->code} added successfully."
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Void Folio Item
    |--------------------------------------------------------------------------
    */
    public function void(
        FolioItem $folioItem
    ) {
        /*
        |--------------------------------------------------------------------------
        | Property
        |--------------------------------------------------------------------------
        */
        $property =
            Property::firstOrFail();


        /*
        |--------------------------------------------------------------------------
        | Security
        |--------------------------------------------------------------------------
        */
        abort_if(
            (int) $folioItem->property_id
            !==
            (int) $property->id,
            404
        );


        /*
        |--------------------------------------------------------------------------
        | Reservation
        |--------------------------------------------------------------------------
        */
        $reservation =
            Reservation::where(
                'property_id',
                $property->id
            )
                ->findOrFail(
                    $folioItem->reservation_id
                );


        /*
        |--------------------------------------------------------------------------
        | Already Voided
        |--------------------------------------------------------------------------
        */
        if (
            $folioItem->status
            !==
            'active'
        ) {

            return redirect()
                ->route(
                    'pms.reservations.show',
                    $reservation
                )
                ->with(
                    'error',
                    "Charge {$folioItem->code} is already voided."
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Transaction
        |--------------------------------------------------------------------------
        */
        DB::transaction(
            function () use (
                $folioItem,
                $reservation
            ) {

                /*
                |--------------------------------------------------------------------------
                | Lock Reservation
                |--------------------------------------------------------------------------
                */
                $lockedReservation =
                    Reservation::whereKey(
                        $reservation->id
                    )
                        ->lockForUpdate()
                        ->firstOrFail();


                /*
                |--------------------------------------------------------------------------
                | Base Total
                |--------------------------------------------------------------------------
                */
                $baseTotal =
                    (float) $lockedReservation
                        ->subtotal
                    +
                    (float) $lockedReservation
                        ->tax_amount
                    +
                    (float) $lockedReservation
                        ->fee_amount;


                /*
                |--------------------------------------------------------------------------
                | Extra Charges Without Current Item
                |--------------------------------------------------------------------------
                */
                $remainingExtras =
                    (float) FolioItem::where(
                        'reservation_id',
                        $lockedReservation->id
                    )
                        ->where(
                            'status',
                            'active'
                        )
                        ->where(
                            'id',
                            '!=',
                            $folioItem->id
                        )
                        ->sum(
                            'total_amount'
                        );


                /*
                |--------------------------------------------------------------------------
                | New Total After Void
                |--------------------------------------------------------------------------
                */
                $newTotal =
                    $baseTotal
                    +
                    $remainingExtras;


                /*
                |--------------------------------------------------------------------------
                | Net Paid
                |--------------------------------------------------------------------------
                */
                $netPaid =
                    $this->calculateNetPaid(
                        $lockedReservation
                    );


                /*
                |--------------------------------------------------------------------------
                | Prevent Negative Balance / Overpayment
                |--------------------------------------------------------------------------
                |
                | Ví dụ:
                |
                | Total hiện tại: 1.500.000
                | Đã thu:       1.500.000
                |
                | Void charge:    300.000
                |
                | New Total:    1.200.000
                |
                | → khách sạn đang giữ dư 300.000
                |
                | Phải Refund trước.
                |
                */
                if (
                    $netPaid
                    >
                    $newTotal
                ) {

                    $refundRequired =
                        $netPaid
                        -
                        $newTotal;


                    throw ValidationException::withMessages([
                        'folio_item' =>
                            'Cannot void this charge yet. Refund '
                            . number_format(
                                $refundRequired,
                                0,
                                ',',
                                '.'
                            )
                            . ' ₫ to the guest first.',
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Void Item
                |--------------------------------------------------------------------------
                */
                $folioItem->update([
                    'status' =>
                        'voided',
                ]);


                /*
                |--------------------------------------------------------------------------
                | Refresh Financials
                |--------------------------------------------------------------------------
                */
                $this->refreshReservationFinancials(
                    $lockedReservation
                );
            }
        );


        return redirect()
            ->route(
                'pms.reservations.show',
                $reservation
            )
            ->with(
                'success',
                "Charge {$folioItem->code} voided successfully."
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Refresh Reservation Financials
    |--------------------------------------------------------------------------
    */
    private function refreshReservationFinancials(
        Reservation $reservation
    ): void {

        /*
        |--------------------------------------------------------------------------
        | Base Reservation Charges
        |--------------------------------------------------------------------------
        */
        $baseTotal =
            (float) $reservation->subtotal
            +
            (float) $reservation->tax_amount
            +
            (float) $reservation->fee_amount;


        /*
        |--------------------------------------------------------------------------
        | Extra Charges
        |--------------------------------------------------------------------------
        */
        $extraCharges =
            (float) FolioItem::where(
                'reservation_id',
                $reservation->id
            )
                ->where(
                    'status',
                    'active'
                )
                ->sum(
                    'total_amount'
                );


        /*
        |--------------------------------------------------------------------------
        | New Reservation Total
        |--------------------------------------------------------------------------
        */
        $totalAmount =
            $baseTotal
            +
            $extraCharges;


        /*
        |--------------------------------------------------------------------------
        | Payment Totals
        |--------------------------------------------------------------------------
        */
        $grossPaid =
            (float) Payment::where(
                'reservation_id',
                $reservation->id
            )
                ->where(
                    'status',
                    'completed'
                )
                ->sum(
                    'amount'
                );


        $refunded =
            (float) PaymentRefund::where(
                'reservation_id',
                $reservation->id
            )
                ->where(
                    'status',
                    'completed'
                )
                ->sum(
                    'amount'
                );


        $netPaid =
            max(
                0,
                $grossPaid
                -
                $refunded
            );


        /*
        |--------------------------------------------------------------------------
        | Payment Status
        |--------------------------------------------------------------------------
        */
        if (
            $netPaid
            <=
            0
        ) {

            if (
                $grossPaid
                >
                0
                &&
                $refunded
                >
                0
            ) {

                $paymentStatus =
                    'refunded';

            } else {

                $paymentStatus =
                    'unpaid';
            }

        } elseif (
            $netPaid
            <
            $totalAmount
        ) {

            $paymentStatus =
                'partial';

        } else {

            $paymentStatus =
                'paid';
        }


        /*
        |--------------------------------------------------------------------------
        | Update Reservation
        |--------------------------------------------------------------------------
        */
        $reservation->update([
            'total_amount' =>
                $totalAmount,

            'paid_amount' =>
                $netPaid,

            'payment_status' =>
                $paymentStatus,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Calculate Net Paid
    |--------------------------------------------------------------------------
    */
    private function calculateNetPaid(
        Reservation $reservation
    ): float {

        $grossPaid =
            (float) Payment::where(
                'reservation_id',
                $reservation->id
            )
                ->where(
                    'status',
                    'completed'
                )
                ->sum(
                    'amount'
                );


        $refunds =
            (float) PaymentRefund::where(
                'reservation_id',
                $reservation->id
            )
                ->where(
                    'status',
                    'completed'
                )
                ->sum(
                    'amount'
                );


        return max(
            0,
            $grossPaid
            -
            $refunds
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Generate Folio Code
    |--------------------------------------------------------------------------
    */
    private function generateFolioCode(
        Property $property
    ): string {

        $latestItem =
            FolioItem::where(
                'property_id',
                $property->id
            )
                ->where(
                    'code',
                    'like',
                    'FOL_%'
                )
                ->orderByDesc('id')
                ->first();


        $nextNumber =
            1;


        if ($latestItem) {

            $number =
                (int) str_replace(
                    'FOL_',
                    '',
                    $latestItem->code
                );


            $nextNumber =
                $number + 1;
        }


        return 'FOL_'
            . str_pad(
                (string) $nextNumber,
                6,
                '0',
                STR_PAD_LEFT
            );
    }
}