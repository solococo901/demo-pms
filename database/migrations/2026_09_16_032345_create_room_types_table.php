<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_types', function (Blueprint $table) {
            $table->id();

            $table->foreignId('property_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');

            $table->string('code');

            $table->text('description')->nullable();

            // Tổng số phòng thuộc Room Type này
            $table->unsignedInteger('total_rooms')->default(1);

            // Occupancy
            $table->unsignedInteger('max_adults')->default(2);
            $table->unsignedInteger('max_children')->default(0);

            // Giá cơ bản để demo
            $table->decimal('base_price', 15, 2)->default(0);

            $table->string('status')->default('active');

            // Mapping với Channex
            $table->string('channex_room_type_id')
                ->nullable()
                ->unique();

            $table->timestamps();

            $table->unique([
                'property_id',
                'code',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_types');
    }
};