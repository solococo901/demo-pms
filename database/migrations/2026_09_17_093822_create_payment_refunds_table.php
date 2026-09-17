<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_refunds', function (Blueprint $table) {
            $table->id();

            $table->foreignId('property_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('reservation_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('payment_id')
                ->constrained()
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Refund Code
            |--------------------------------------------------------------------------
            |
            | REF_000001
            |
            */
            $table->string('code');

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

            $table->string('currency')
                ->default('VND');

            /*
            |--------------------------------------------------------------------------
            | Refund Method
            |--------------------------------------------------------------------------
            |
            | cash
            | bank_transfer
            | card
            | payment_gateway
            | ota
            | other
            |
            */
            $table->string('refund_method')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Provider
            |--------------------------------------------------------------------------
            */
            $table->string('provider')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Refund Transaction Reference
            |--------------------------------------------------------------------------
            */
            $table->string('transaction_reference')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Reason
            |--------------------------------------------------------------------------
            */
            $table->string('reason')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            |
            | completed
            | voided
            |
            */
            $table->string('status')
                ->default('completed');

            $table->timestamp('refunded_at')
                ->nullable();

            $table->text('notes')
                ->nullable();

            $table->timestamps();

            $table->unique([
                'property_id',
                'code',
            ]);

            $table->index([
                'reservation_id',
                'status',
            ]);

            $table->index([
                'payment_id',
                'status',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'payment_refunds'
        );
    }
};