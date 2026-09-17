<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Property
            |--------------------------------------------------------------------------
            */
            $table->foreignId('property_id')
                ->constrained()
                ->cascadeOnDelete();


            /*
            |--------------------------------------------------------------------------
            | Reservation
            |--------------------------------------------------------------------------
            */
            $table->foreignId('reservation_id')
                ->constrained()
                ->cascadeOnDelete();


            /*
            |--------------------------------------------------------------------------
            | Guest
            |--------------------------------------------------------------------------
            |
            | Snapshot liên kết Guest tại thời điểm payment.
            |
            */
            $table->foreignId('guest_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();


            /*
            |--------------------------------------------------------------------------
            | Payment Code
            |--------------------------------------------------------------------------
            |
            | PAY_000001
            |
            */
            $table->string('code');


            /*
            |--------------------------------------------------------------------------
            | Payment Method
            |--------------------------------------------------------------------------
            |
            | cash
            | bank_transfer
            | card
            | payment_gateway
            | ota_collect
            | other
            |
            */
            $table->string('payment_method');


            /*
            |--------------------------------------------------------------------------
            | Provider
            |--------------------------------------------------------------------------
            |
            | Ví dụ:
            |
            | VNPay
            | MoMo
            | Stripe
            | Booking.com
            | Agoda
            | Vietcombank
            |
            */
            $table->string('provider')
                ->nullable();


            /*
            |--------------------------------------------------------------------------
            | Transaction Reference
            |--------------------------------------------------------------------------
            |
            | Mã giao dịch ngân hàng /
            | payment gateway / OTA.
            |
            */
            $table->string('transaction_reference')
                ->nullable();


            /*
            |--------------------------------------------------------------------------
            | Amount
            |--------------------------------------------------------------------------
            */
            $table->decimal(
                'amount',
                15,
                2
            );


            /*
            |--------------------------------------------------------------------------
            | Currency
            |--------------------------------------------------------------------------
            */
            $table->string('currency')
                ->default('VND');


            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            |
            | completed
            | voided
            | refunded
            |
            */
            $table->string('status')
                ->default('completed');


            /*
            |--------------------------------------------------------------------------
            | Payment Time
            |--------------------------------------------------------------------------
            */
            $table->timestamp('paid_at')
                ->nullable();


            /*
            |--------------------------------------------------------------------------
            | Internal Notes
            |--------------------------------------------------------------------------
            */
            $table->text('notes')
                ->nullable();


            $table->timestamps();


            /*
            |--------------------------------------------------------------------------
            | Unique Payment Code Per Property
            |--------------------------------------------------------------------------
            */
            $table->unique([
                'property_id',
                'code',
            ]);


            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            */
            $table->index([
                'reservation_id',
                'status',
            ]);

            $table->index(
                'transaction_reference'
            );
        });
    }


    public function down(): void
    {
        Schema::dropIfExists(
            'payments'
        );
    }
};