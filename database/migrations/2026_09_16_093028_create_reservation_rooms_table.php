<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_rooms', function (Blueprint $table) {
            $table->id();

            /*
             * Reservation cha.
             */
            $table->foreignId('reservation_id')
                ->constrained()
                ->cascadeOnDelete();

            /*
             * Loại phòng khách đã book.
             *
             * Deluxe / Studio / Suite
             */
            $table->foreignId('room_type_id')
                ->constrained()
                ->restrictOnDelete();

            /*
             * Rate Plan.
             *
             * BAR
             * Non Refundable
             */
            $table->foreignId('rate_plan_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            /*
             * Physical Room.
             *
             * Nullable vì lúc booking
             * có thể chưa assign phòng cụ thể.
             *
             * Ví dụ:
             * Book Deluxe hôm nay
             * nhưng đến ngày check-in mới assign Room 201.
             */
            $table->foreignId('room_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            /*
             * Stay dates.
             */
            $table->date('check_in');

            $table->date('check_out');

            /*
             * Occupancy.
             */
            $table->unsignedInteger('adults')
                ->default(1);

            $table->unsignedInteger('children')
                ->default(0);

            /*
             * Giá cơ bản của phòng khi booking.
             *
             * Phase 1:
             * dùng một nightly rate.
             *
             * Sau này sẽ có bảng
             * reservation_room_nights để lưu
             * giá riêng từng ngày.
             */
            $table->decimal(
                'nightly_rate',
                15,
                2
            )->default(0);

            /*
             * Tổng tiền của room line này.
             */
            $table->decimal(
                'total_amount',
                15,
                2
            )->default(0);

            /*
             * Ghi chú riêng cho room.
             */
            $table->text('notes')
                ->nullable();

            $table->timestamps();


            /*
             * Dùng rất nhiều khi kiểm tra
             * availability theo ngày.
             */
            $table->index([
                'room_type_id',
                'check_in',
                'check_out',
            ]);

            /*
             * Front Desk cần tìm booking
             * của Physical Room nhanh.
             */
            $table->index([
                'room_id',
                'check_in',
                'check_out',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'reservation_rooms'
        );
    }
};