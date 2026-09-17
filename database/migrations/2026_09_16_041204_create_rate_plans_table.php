<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rate_plans', function (Blueprint $table) {
            $table->id();

            $table->foreignId('property_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('room_type_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('code');

            $table->decimal('base_rate', 15, 2)
                ->default(0);

            $table->unsignedInteger('min_stay')
                ->default(1);

            $table->boolean('stop_sell')
                ->default(false);

            $table->string('status')
                ->default('active');

            // Channex mapping
            $table->string('channex_rate_plan_id')
                ->nullable()
                ->unique();

            $table->string('channex_sell_mode')
                ->nullable();

            $table->timestamps();

            $table->unique([
                'room_type_id',
                'code',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rate_plans');
    }
};