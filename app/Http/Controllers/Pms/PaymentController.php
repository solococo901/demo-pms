<?php

namespace App\Http\Controllers\Pms;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentRefund;
use App\Models\Property;
use App\Models\Reservation;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Record Payment
    |--------------------------------------------------------------------------
    */
    public function store(
        Request $request,
        Reservation $reservation
    ) {
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
        | Validate
        |--------------------------------------------------------------------------
        */
        $validated =
            $request->validate([
                'payment_method' => [
                    'required',

                    Rule::in([
                        'cash',
                        'bank_transfer',
                        'card',
                        'payment_gateway',
                        'ota_collect',
                        'other',
                    ]),
                ],

                'provider' => [
                    'nullable',
                    'string',
                    'max:100',
                ],

                'transaction_reference' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'amount' => [
                    'required',
                    'numeric',
                    'gt:0',
                ],

                'paid_at' => [
                    'nullable',
                    'date',
                ],

                'notes' => [
                    'nullable',
                    'string',
                    'max:2000',
                ],
            ]);


        $timezone =
            $property->timezone
            ?? 'Asia/Ho_Chi_Minh';


        /*
        |--------------------------------------------------------------------------
        | Transaction
        |--------------------------------------------------------------------------
        */
        $payment =
            DB::transaction(
                function () use ($property, $reservation, $validated, $timezone) {

                    $lockedReservation =
                        Reservation::whereKey(
                            $reservation->id
                        )
                            ->lockForUpdate()
                            ->firstOrFail();


                    /*
                    |--------------------------------------------------------------------------
                    | Net Paid
                    |--------------------------------------------------------------------------
                    |
                    | Payments
                    | -
                    | Refunds
                    |
                    */
                    $netPaid =
                        $this->calculateNetPaid(
                            $lockedReservation
                        );


                    $totalAmount =
                        (float) $lockedReservation
                            ->total_amount;


                    $balance =
                        max(
                            0,
                            $totalAmount
                            -
                            $netPaid
                        );


                    $paymentAmount =
                        (float) $validated[
                            'amount'
                        ];


                    /*
                    |--------------------------------------------------------------------------
                    | Already Fully Paid
                    |--------------------------------------------------------------------------
                    */
                    if (
                        $balance
                        <=
                        0
                    ) {

                        throw ValidationException::withMessages([
                            'amount' =>
                                'This reservation has already been fully paid.',
                        ]);
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Prevent Overpayment
                    |--------------------------------------------------------------------------
                    */
                    if (
                        $paymentAmount
                        >
                        $balance
                    ) {

                        throw ValidationException::withMessages([
                            'amount' =>
                                'Payment amount cannot exceed the outstanding balance of '
                                . number_format(
                                    $balance,
                                    0,
                                    ',',
                                    '.'
                                )
                                . ' ₫.',
                        ]);
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Paid At
                    |--------------------------------------------------------------------------
                    */
                    $paidAt =
                        !empty(
                        $validated['paid_at']
                    )
                        ? CarbonImmutable::parse(
                            $validated['paid_at'],
                            $timezone
                        )
                        : CarbonImmutable::now(
                            $timezone
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | Create Payment
                    |--------------------------------------------------------------------------
                    */
                    $payment =
                        Payment::create([
                            'property_id' =>
                                $property->id,

                            'reservation_id' =>
                                $lockedReservation->id,

                            'guest_id' =>
                                $lockedReservation->guest_id,

                            'code' =>
                                $this->generatePaymentCode(
                                    $property
                                ),

                            'payment_method' =>
                                $validated['payment_method'],

                            'provider' =>
                                $validated['provider']
                                ?? null,

                            'transaction_reference' =>
                                $validated[
                                    'transaction_reference'
                                ]
                                ?? null,

                            'amount' =>
                                $paymentAmount,

                            'currency' =>
                                $lockedReservation->currency
                                ?? $property->currency
                                ?? 'VND',

                            'status' =>
                                'completed',

                            'paid_at' =>
                                $paidAt,

                            'notes' =>
                                $validated['notes']
                                ?? null,
                        ]);


                    /*
                    |--------------------------------------------------------------------------
                    | Refresh Reservation
                    |--------------------------------------------------------------------------
                    */
                    $this->refreshReservationPaymentSummary(
                        $lockedReservation
                    );


                    return $payment;
                }
            );


        return redirect()
            ->route(
                'pms.reservations.show',
                $reservation
            )
            ->with(
                'success',
                "Payment {$payment->code} recorded successfully."
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Void Payment
    |--------------------------------------------------------------------------
    */
    public function void(
        Payment $payment
    ) {
        $property =
            Property::firstOrFail();


        abort_if(
            (int) $payment->property_id
            !==
            (int) $property->id,
            404
        );


        $reservation =
            Reservation::where(
                'property_id',
                $property->id
            )
                ->findOrFail(
                    $payment->reservation_id
                );


        /*
        |--------------------------------------------------------------------------
        | Only Completed Payment
        |--------------------------------------------------------------------------
        */
        if (
            $payment->status
            !==
            'completed'
        ) {

            return redirect()
                ->route(
                    'pms.reservations.show',
                    $reservation
                )
                ->with(
                    'error',
                    "Payment {$payment->code} cannot be voided."
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Cannot Void Refunded Payment
        |--------------------------------------------------------------------------
        |
        | Nếu payment đã có refund thật,
        | không void payment gốc nữa.
        |
        */
        $hasRefunds =
            PaymentRefund::where(
                'payment_id',
                $payment->id
            )
                ->where(
                    'status',
                    'completed'
                )
                ->exists();


        if ($hasRefunds) {

            return redirect()
                ->route(
                    'pms.reservations.show',
                    $reservation
                )
                ->with(
                    'error',
                    'This payment already has a refund and cannot be voided.'
                );
        }


        DB::transaction(
            function () use ($payment, $reservation) {

                $lockedReservation =
                    Reservation::whereKey(
                        $reservation->id
                    )
                        ->lockForUpdate()
                        ->firstOrFail();


                $payment->update([
                    'status' =>
                        'voided',
                ]);


                $this->refreshReservationPaymentSummary(
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
                "Payment {$payment->code} voided successfully."
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Refund Payment
    |--------------------------------------------------------------------------
    */
    public function refund(
        Request $request,
        Payment $payment
    ) {
        $property =
            Property::firstOrFail();


        /*
        |--------------------------------------------------------------------------
        | Security
        |--------------------------------------------------------------------------
        */
        abort_if(
            (int) $payment->property_id
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
                    $payment->reservation_id
                );


        /*
        |--------------------------------------------------------------------------
        | Only Completed Payment Can Refund
        |--------------------------------------------------------------------------
        */
        if (
            $payment->status
            !==
            'completed'
        ) {

            return redirect()
                ->route(
                    'pms.reservations.show',
                    $reservation
                )
                ->with(
                    'error',
                    "Payment {$payment->code} cannot be refunded."
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Validate
        |--------------------------------------------------------------------------
        */
        $validated =
            $request->validate([
                'amount' => [
                    'required',
                    'numeric',
                    'gt:0',
                ],

                'refund_method' => [
                    'nullable',

                    Rule::in([
                        'cash',
                        'bank_transfer',
                        'card',
                        'payment_gateway',
                        'ota',
                        'other',
                    ]),
                ],

                'provider' => [
                    'nullable',
                    'string',
                    'max:100',
                ],

                'transaction_reference' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'reason' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'refunded_at' => [
                    'nullable',
                    'date',
                ],

                'notes' => [
                    'nullable',
                    'string',
                    'max:2000',
                ],
            ]);


        $timezone =
            $property->timezone
            ?? 'Asia/Ho_Chi_Minh';


        /*
        |--------------------------------------------------------------------------
        | Create Refund
        |--------------------------------------------------------------------------
        */
        $refund =
            DB::transaction(
                function () use ($property, $reservation, $payment, $validated, $timezone) {

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
                    | Lock Payment
                    |--------------------------------------------------------------------------
                    */
                    $lockedPayment =
                        Payment::whereKey(
                            $payment->id
                        )
                            ->lockForUpdate()
                            ->firstOrFail();


                    if (
                        $lockedPayment->status
                        !==
                        'completed'
                    ) {

                        throw ValidationException::withMessages([
                            'amount' =>
                                'This payment is no longer refundable.',
                        ]);
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Already Refunded Amount
                    |--------------------------------------------------------------------------
                    */
                    $alreadyRefunded =
                        (float) PaymentRefund::where(
                            'payment_id',
                            $lockedPayment->id
                        )
                            ->where(
                                'status',
                                'completed'
                            )
                            ->sum(
                                'amount'
                            );


                    /*
                    |--------------------------------------------------------------------------
                    | Refundable
                    |--------------------------------------------------------------------------
                    */
                    $refundableAmount =
                        max(
                            0,
                            (float) $lockedPayment->amount
                            -
                            $alreadyRefunded
                        );


                    $refundAmount =
                        (float) $validated[
                            'amount'
                        ];


                    /*
                    |--------------------------------------------------------------------------
                    | Nothing Left To Refund
                    |--------------------------------------------------------------------------
                    */
                    if (
                        $refundableAmount
                        <=
                        0
                    ) {

                        throw ValidationException::withMessages([
                            'amount' =>
                                'This payment has already been fully refunded.',
                        ]);
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Prevent Over Refund
                    |--------------------------------------------------------------------------
                    */
                    if (
                        $refundAmount
                        >
                        $refundableAmount
                    ) {

                        throw ValidationException::withMessages([
                            'amount' =>
                                'Refund amount cannot exceed '
                                . number_format(
                                    $refundableAmount,
                                    0,
                                    ',',
                                    '.'
                                )
                                . ' ₫.',
                        ]);
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Refunded At
                    |--------------------------------------------------------------------------
                    */
                    $refundedAt =
                        !empty(
                        $validated[
                            'refunded_at'
                        ]
                    )
                        ? CarbonImmutable::parse(
                            $validated[
                                'refunded_at'
                            ],
                            $timezone
                        )
                        : CarbonImmutable::now(
                            $timezone
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | Create Refund
                    |--------------------------------------------------------------------------
                    */
                    $refund =
                        PaymentRefund::create([
                            'property_id' =>
                                $property->id,

                            'reservation_id' =>
                                $lockedReservation->id,

                            'payment_id' =>
                                $lockedPayment->id,

                            'code' =>
                                $this->generateRefundCode(
                                    $property
                                ),

                            'amount' =>
                                $refundAmount,

                            'currency' =>
                                $lockedPayment->currency
                                ?? 'VND',

                            'refund_method' =>
                                $validated[
                                    'refund_method'
                                ]
                                ?? $lockedPayment
                                    ->payment_method,

                            'provider' =>
                                $validated[
                                    'provider'
                                ]
                                ?? $lockedPayment
                                    ->provider,

                            'transaction_reference' =>
                                $validated[
                                    'transaction_reference'
                                ]
                                ?? null,

                            'reason' =>
                                $validated[
                                    'reason'
                                ]
                                ?? null,

                            'status' =>
                                'completed',

                            'refunded_at' =>
                                $refundedAt,

                            'notes' =>
                                $validated[
                                    'notes'
                                ]
                                ?? null,
                        ]);


                    /*
                    |--------------------------------------------------------------------------
                    | Refresh Reservation Summary
                    |--------------------------------------------------------------------------
                    */
                    $this->refreshReservationPaymentSummary(
                        $lockedReservation
                    );


                    return $refund;
                }
            );


        return redirect()
            ->route(
                'pms.reservations.show',
                $reservation
            )
            ->with(
                'success',
                "Refund {$refund->code} recorded successfully."
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Void Refund
    |--------------------------------------------------------------------------
    */
    public function voidRefund(
        PaymentRefund $refund
    ) {
        $property =
            Property::firstOrFail();


        /*
        |--------------------------------------------------------------------------
        | Security
        |--------------------------------------------------------------------------
        */
        abort_if(
            (int) $refund->property_id
            !==
            (int) $property->id,
            404
        );


        $reservation =
            Reservation::where(
                'property_id',
                $property->id
            )
                ->findOrFail(
                    $refund->reservation_id
                );


        /*
        |--------------------------------------------------------------------------
        | Only Completed Refund
        |--------------------------------------------------------------------------
        */
        if (
            $refund->status
            !==
            'completed'
        ) {

            return redirect()
                ->route(
                    'pms.reservations.show',
                    $reservation
                )
                ->with(
                    'error',
                    "Refund {$refund->code} cannot be voided."
                );
        }


        DB::transaction(
            function () use ($refund, $reservation) {

                $lockedReservation =
                    Reservation::whereKey(
                        $reservation->id
                    )
                        ->lockForUpdate()
                        ->firstOrFail();


                $refund->update([
                    'status' =>
                        'voided',
                ]);


                $this->refreshReservationPaymentSummary(
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
                "Refund {$refund->code} voided successfully."
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Calculate Completed Payments
    |--------------------------------------------------------------------------
    */
    private function calculateCompletedPayments(
        Reservation $reservation
    ): float {

        return (float) Payment::where(
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
    }


    /*
    |--------------------------------------------------------------------------
    | Calculate Completed Refunds
    |--------------------------------------------------------------------------
    */
    private function calculateCompletedRefunds(
        Reservation $reservation
    ): float {

        return (float) PaymentRefund::where(
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
    }


    /*
    |--------------------------------------------------------------------------
    | Calculate Net Paid
    |--------------------------------------------------------------------------
    */
    private function calculateNetPaid(
        Reservation $reservation
    ): float {

        $payments =
            $this->calculateCompletedPayments(
                $reservation
            );


        $refunds =
            $this->calculateCompletedRefunds(
                $reservation
            );


        return max(
            0,
            $payments
            -
            $refunds
        );
    }


    /*
|--------------------------------------------------------------------------
| Refresh Reservation Payment Summary
|--------------------------------------------------------------------------
*/
    private function refreshReservationPaymentSummary(
        Reservation $reservation
    ): void {

        /*
        |--------------------------------------------------------------------------
        | Gross Paid
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


        /*
        |--------------------------------------------------------------------------
        | Refunded
        |--------------------------------------------------------------------------
        */
        $refundedAmount =
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


        /*
        |--------------------------------------------------------------------------
        | Net Paid
        |--------------------------------------------------------------------------
        */
        $netPaid =
            max(
                0,
                $grossPaid
                -
                $refundedAmount
            );


        /*
        |--------------------------------------------------------------------------
        | Total
        |--------------------------------------------------------------------------
        */
        $totalAmount =
            (float) $reservation
                ->total_amount;


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
                $refundedAmount
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
            /*
             * paid_amount = NET PAID
             *
             * Không phải Gross Paid.
             */
            'paid_amount' =>
                $netPaid,

            'payment_status' =>
                $paymentStatus,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Generate Payment Code
    |--------------------------------------------------------------------------
    */
    private function generatePaymentCode(
        Property $property
    ): string {

        $latestPayment =
            Payment::where(
                'property_id',
                $property->id
            )
                ->where(
                    'code',
                    'like',
                    'PAY_%'
                )
                ->orderByDesc('id')
                ->first();


        $nextNumber =
            1;


        if ($latestPayment) {

            $number =
                (int) str_replace(
                    'PAY_',
                    '',
                    $latestPayment->code
                );


            $nextNumber =
                $number + 1;
        }


        return 'PAY_'
            . str_pad(
                (string) $nextNumber,
                6,
                '0',
                STR_PAD_LEFT
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Generate Refund Code
    |--------------------------------------------------------------------------
    */
    private function generateRefundCode(
        Property $property
    ): string {

        $latestRefund =
            PaymentRefund::where(
                'property_id',
                $property->id
            )
                ->where(
                    'code',
                    'like',
                    'REF_%'
                )
                ->orderByDesc('id')
                ->first();


        $nextNumber =
            1;


        if ($latestRefund) {

            $number =
                (int) str_replace(
                    'REF_',
                    '',
                    $latestRefund->code
                );


            $nextNumber =
                $number + 1;
        }


        return 'REF_'
            . str_pad(
                (string) $nextNumber,
                6,
                '0',
                STR_PAD_LEFT
            );
    }
}