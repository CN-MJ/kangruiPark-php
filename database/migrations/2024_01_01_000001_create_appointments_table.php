<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 预约表
     */
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->string('user_name', 50)->comment('用户姓名');
            $table->string('user_phone', 20)->comment('用户手机号');
            $table->dateTime('appointment_time')->comment('预约时间');
            $table->string('resource_name', 100)->comment('预约项目/资源名称');
            $table->tinyInteger('status')->default(0)->comment('状态: 0=有效, 1=已取消');
            $table->timestamps();

            $table->index('user_phone');
            $table->index('status');
            $table->index('appointment_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
