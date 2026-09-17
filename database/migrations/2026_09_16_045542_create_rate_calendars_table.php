<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rate_calendars', function (Blueprint $table) {
            $table->id();

            $table->foreignId('property_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('rate_plan_id')
                ->constrained()
                ->cascadeOnDelete();

            /*
             * Ngày áp dụng giá.
             */
            $table->date('date');

            /*
             * Giá bán trong ngày.
             */
            $table->decimal(
                'rate',
                15,
                2
            )->default(0);

            /*
             * Phase 1:
             * PMS gọi tên là min_stay.
             *
             * Khi sync Channex sẽ gửi thành:
             * min_stay_arrival
             */
            $table->unsignedInteger(
                'min_stay'
            )->default(1);

            /*
             * Có đóng bán hay không.
             */
            $table->boolean(
                'stop_sell'
            )->default(false);

            /*
             * Trạng thái sync Channex.
             */
            $table->string(
                'sync_status'
            )->default('pending');

            $table->timestamp(
                'synced_at'
            )->nullable();

            $table->timestamps();


            /*
             * Mỗi Rate Plan chỉ có một record
             * cho mỗi ngày.
             */
            $table->unique([
                'rate_plan_id',
                'date',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'rate_calendars'
        );
    }
};