<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();

            /*
             * Property mà phòng này thuộc về.
             */
            $table->foreignId('property_id')
                ->constrained()
                ->cascadeOnDelete();

            /*
             * Room Type.
             *
             * Ví dụ:
             * Room 101 → Deluxe
             */
            $table->foreignId('room_type_id')
                ->constrained()
                ->cascadeOnDelete();

            /*
             * Số / mã phòng hiển thị cho vận hành.
             *
             * Ví dụ:
             * 101
             * 1203
             * A-01
             */
            $table->string('room_number');

            /*
             * Tầng.
             *
             * Ví dụ:
             * 1
             * 2
             * 12
             */
            $table->string('floor')
                ->nullable();

            /*
             * Trạng thái vận hành chính.
             *
             * available
             * occupied
             * out_of_order
             * maintenance
             */
            $table->string('status')
                ->default('available');

            /*
             * Trạng thái housekeeping.
             *
             * clean
             * dirty
             * cleaning
             * inspected
             */
            $table->string('housekeeping_status')
                ->default('clean');

            /*
             * Ghi chú nội bộ.
             *
             * Ví dụ:
             * Gần thang máy
             * Phòng góc
             * Máy lạnh đang kiểm tra
             */
            $table->text('notes')
                ->nullable();

            $table->timestamps();

            /*
             * Trong cùng một Property,
             * không được có hai phòng cùng số.
             */
            $table->unique([
                'property_id',
                'room_number',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};