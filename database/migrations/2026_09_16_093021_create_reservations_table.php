<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();

            /*
             * Hotel / Property.
             */
            $table->foreignId('property_id')
                ->constrained()
                ->cascadeOnDelete();

            /*
             * Primary Guest.
             *
             * Nullable để sau này booking từ OTA
             * vẫn có thể import trước khi Guest Profile hoàn chỉnh.
             */
            $table->foreignId('guest_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            /*
             * Mã booking nội bộ PMS.
             *
             * RES_000001
             */
            $table->string('code');

            /*
             * Nguồn booking.
             *
             * direct
             * website
             * walk_in
             * phone
             * channex
             * ota
             */
            $table->string('source')
                ->default('direct');

            /*
             * Tên channel cụ thể.
             *
             * Booking.com
             * Agoda
             * Expedia
             */
            $table->string('channel')
                ->nullable();

            /*
             * Mã reservation của hệ thống ngoài.
             */
            $table->string('external_reservation_id')
                ->nullable();

            /*
             * Booking ID từ Channex.
             */
            $table->string('channex_booking_id')
                ->nullable();

            /*
             * Trạng thái booking.
             *
             * pending
             * confirmed
             * checked_in
             * checked_out
             * cancelled
             * no_show
             */
            $table->string('status')
                ->default('confirmed');

            /*
             * Trạng thái thanh toán.
             *
             * unpaid
             * partial
             * paid
             * refunded
             */
            $table->string('payment_status')
                ->default('unpaid');

            /*
             * Currency.
             */
            $table->string('currency')
                ->default('VND');

            /*
             * Tiền phòng trước thuế/phí.
             */
            $table->decimal(
                'subtotal',
                15,
                2
            )->default(0);

            /*
             * Thuế.
             */
            $table->decimal(
                'tax_amount',
                15,
                2
            )->default(0);

            /*
             * Phí.
             */
            $table->decimal(
                'fee_amount',
                15,
                2
            )->default(0);

            /*
             * Tổng tiền booking.
             */
            $table->decimal(
                'total_amount',
                15,
                2
            )->default(0);

            /*
             * Đã thanh toán.
             */
            $table->decimal(
                'paid_amount',
                15,
                2
            )->default(0);

            /*
             * Yêu cầu của khách.
             *
             * High floor
             * Late arrival
             * Twin beds
             */
            $table->text('special_requests')
                ->nullable();

            /*
             * Ghi chú nội bộ.
             */
            $table->text('notes')
                ->nullable();

            /*
             * Booking được tạo lúc nào.
             */
            $table->timestamp('booked_at')
                ->nullable();

            /*
             * Nếu cancel.
             */
            $table->timestamp('cancelled_at')
                ->nullable();

            $table->timestamps();


            /*
             * RES code không được trùng
             * trong cùng Property.
             */
            $table->unique([
                'property_id',
                'code',
            ]);

            /*
             * Tăng tốc tìm booking external.
             */
            $table->index(
                'external_reservation_id'
            );

            $table->index(
                'channex_booking_id'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'reservations'
        );
    }
};