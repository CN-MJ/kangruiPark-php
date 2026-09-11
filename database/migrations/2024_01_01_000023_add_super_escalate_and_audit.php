<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('xcx_users', function (Blueprint $table) {
            $table->boolean('can_escalate')->default(false)->after('phone_verified_at')
                ->comment('是否可升权进入超级控制台，管理端不展示');
        });

        Schema::create('super_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('xcx_users')->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamps();
        });

        Schema::create('admin_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('phone', 20)->nullable()->index();
            $table->string('action', 64)->index();
            $table->string('target_type', 64)->nullable();
            $table->string('target_id', 64)->nullable();
            $table->json('payload')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_audit_logs');
        Schema::dropIfExists('super_tokens');
        Schema::table('xcx_users', function (Blueprint $table) {
            $table->dropColumn('can_escalate');
        });
    }
};
