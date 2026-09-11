<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parking_devices', function (Blueprint $table) {
            $table->id();
            $table->string('device_name', 100)->comment('设备名称');
            $table->tinyInteger('device_type')->comment('1=入口摄像头,2=出口摄像头,3=道闸');
            $table->tinyInteger('direction')->comment('1=入口,2=出口');
            $table->string('location', 120)->nullable()->comment('安装位置');
            $table->string('api_key', 64)->unique()->comment('设备鉴权密钥');
            $table->tinyInteger('status')->default(1)->comment('1=正常,0=停用');
            $table->timestamp('last_heartbeat_at')->nullable()->comment('最近心跳时间');
            $table->timestamps();

            $table->index('status');
            $table->index('direction');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parking_devices');
    }
};
