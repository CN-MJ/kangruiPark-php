<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 停车记录表
     */
    public function up(): void
    {
        Schema::create('parking_records', function (Blueprint $table) {
            $table->id();
            $table->string('plate_number', 20)->comment('车牌号');
            $table->dateTime('entry_time')->comment('入场时间');
            $table->dateTime('exit_time')->nullable()->comment('出场时间');
            $table->tinyInteger('status')->default(0)->comment('状态: 0=未离场, 1=已离场');
            $table->timestamps();

            $table->index('plate_number');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parking_records');
    }
};
