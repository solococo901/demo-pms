<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('folio_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('property_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('reservation_id')
                ->constrained()
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Folio Item Code
            |--------------------------------------------------------------------------
            |
            | FOL_000001
            |
            */
            $table->string('code');

            /*
            |--------------------------------------------------------------------------
            | Category
            |--------------------------------------------------------------------------
            |
            | minibar
            | laundry
            | airport_transfer
            | extra_bed
            | late_checkout
            | damage_fee
            | other
            |
            */
            $table->string('category');

            /*
            |--------------------------------------------------------------------------
            | Description
            |--------------------------------------------------------------------------
            */
            $table->string('description');

            /*
            |--------------------------------------------------------------------------
            | Quantity
            |--------------------------------------------------------------------------
            */
            $table->unsignedInteger('quantity')
                ->default(1);

            /*
            |--------------------------------------------------------------------------
            | Unit Price
            |--------------------------------------------------------------------------
            */
            $table->decimal(
                'unit_price',
                15,
                2
            )
                ->default(0);

            /*
            |--------------------------------------------------------------------------
            | Total
            |--------------------------------------------------------------------------
            */
            $table->decimal(
                'total_amount',
                15,
                2
            )
                ->default(0);

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
            | active
            | voided
            |
            */
            $table->string('status')
                ->default('active');

            /*
            |--------------------------------------------------------------------------
            | Posted At
            |--------------------------------------------------------------------------
            */
            $table->timestamp('posted_at')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Notes
            |--------------------------------------------------------------------------
            */
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

            $table->index(
                'category'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'folio_items'
        );
    }
};