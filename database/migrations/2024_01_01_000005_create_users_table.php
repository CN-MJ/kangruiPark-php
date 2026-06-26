<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('xcx_users', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20)->unique()->comment('手机号');
            $table->string('name', 50)->default('')->comment('姓名');
            $table->string('role', 20)->default('visitor')->comment('visitor=访客, merchant=商户, park=园区');
            $table->timestamp('checked_in_at')->nullable()->comment('入住/签到时间');
            $table->timestamps();

            $table->index('role');
        });

        Schema::create('user_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('xcx_users')->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_tokens');
        Schema::dropIfExists('xcx_users');
    }
};
