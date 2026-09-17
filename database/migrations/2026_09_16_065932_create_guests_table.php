<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guests', function (Blueprint $table) {
            $table->id();

            /*
             * Property sở hữu hồ sơ khách.
             *
             * Demo hiện tại chỉ có 1 Property,
             * nhưng giữ field này để sau scale multi-property.
             */
            $table->foreignId('property_id')
                ->constrained()
                ->cascadeOnDelete();

            /*
             * Mã Guest nội bộ PMS.
             *
             * Ví dụ:
             * GST_000001
             */
            $table->string('code');

            /*
             * Thông tin cơ bản.
             */
            $table->string('first_name');

            $table->string('last_name')
                ->nullable();

            $table->string('email')
                ->nullable();

            $table->string('phone')
                ->nullable();

            /*
             * Quốc tịch.
             *
             * Demo có thể lưu:
             * VN
             * US
             * KR
             */
            $table->string('nationality', 10)
                ->nullable();

            /*
             * Ngày sinh.
             */
            $table->date('date_of_birth')
                ->nullable();

            /*
             * Gender Phase 1.
             */
            $table->string('gender')
                ->nullable();

            /*
             * Loại giấy tờ.
             *
             * national_id
             * passport
             * driver_license
             * other
             */
            $table->string('id_type')
                ->nullable();

            /*
             * CCCD / Passport number.
             */
            $table->string('id_number')
                ->nullable();

            /*
             * Địa chỉ khách.
             */
            $table->text('address')
                ->nullable();

            /*
             * Ghi chú vận hành.
             *
             * Ví dụ:
             * - Prefer high floor
             * - Late arrival
             * - Returning guest
             */
            $table->text('notes')
                ->nullable();

            /*
             * Guest profile status.
             */
            $table->string('status')
                ->default('active');

            $table->timestamps();


            /*
             * Guest Code không được trùng
             * trong cùng Property.
             */
            $table->unique([
                'property_id',
                'code',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guests');
    }
};