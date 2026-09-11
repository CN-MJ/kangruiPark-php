<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parking_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('parking_devices')->cascadeOnDelete();
            $table->string('plate_number', 20)->comment('识别车牌');
            $table->tinyInteger('direction')->comment('1=入场,2=出场');
            $table->dateTime('recognition_time')->comment('识别时间');
            $table->string('image_url', 500)->nullable()->comment('抓拍图片');
            $table->decimal('confidence', 5, 4)->nullable()->comment('识别置信度');
            $table->string('event_id', 64)->unique()->comment('设备侧唯一事件ID');
            $table->tinyInteger('processed_status')->default(0)->comment('0待处理,1已处理,2已忽略,3异常');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index('plate_number');
            $table->index('processed_status');
            $table->index('direction');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parking_events');
    }
};
