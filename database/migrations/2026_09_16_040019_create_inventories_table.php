<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('property_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('room_type_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->date('date');

            $table->unsignedInteger('availability')
                ->default(0);

            $table->string('sync_status')
                ->default('pending');

            $table->timestamp('synced_at')
                ->nullable();

            $table->timestamps();

            $table->unique([
                'room_type_id',
                'date'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};