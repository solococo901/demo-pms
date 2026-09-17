<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('properties', function (Blueprint $table) {
        $table->id();

        $table->string('code')->unique();
        $table->string('name');

        $table->string('address')->nullable();
        $table->string('city')->nullable();
        $table->string('country')->default('VN');

        $table->string('currency')->default('VND');
        $table->string('timezone')->default('Asia/Ho_Chi_Minh');

        $table->time('check_in_time')->default('14:00');
        $table->time('check_out_time')->default('12:00');

        $table->string('status')->default('active');

        // Mapping với Channex
        $table->string('channex_property_id')->nullable()->unique();

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
