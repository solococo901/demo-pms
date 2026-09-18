<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channex_booking_revisions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('property_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('reservation_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('revision_id')->unique();
            $table->string('channex_booking_id')->nullable();
            $table->string('system_id')->nullable();
            $table->string('revision_status');
            $table->string('ota_reservation_code')->nullable();
            $table->string('ota_name')->nullable();

            /*
            |--------------------------------------------------------------------------
            | processing_status
            |--------------------------------------------------------------------------
            |
            | received
            | processed
            | acknowledged
            | skipped
            | error
            |
            */
            $table->string('processing_status')
                ->default('received');

            $table->json('payload');
            $table->text('error_message')->nullable();

            $table->timestamp('received_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();

            $table->timestamps();

            $table->index('channex_booking_id');
            $table->index('system_id');
            $table->index(['processing_status', 'revision_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channex_booking_revisions');
    }
};
