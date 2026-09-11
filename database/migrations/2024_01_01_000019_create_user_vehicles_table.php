<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('xcx_users')->cascadeOnDelete();
            $table->string('plate_number', 20)->comment('绑定车牌');
            $table->boolean('is_default')->default(false)->comment('是否默认车辆');
            $table->tinyInteger('status')->default(1)->comment('1=启用,0=停用');
            $table->timestamps();

            $table->unique(['user_id', 'plate_number']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_vehicles');
    }
};
