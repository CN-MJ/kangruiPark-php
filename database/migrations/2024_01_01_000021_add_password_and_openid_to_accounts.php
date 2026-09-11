<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('xcx_users', function (Blueprint $table) {
            $table->string('password')->nullable()->after('role')->comment('bcrypt 单向哈希，不可还原');
            $table->string('openid', 64)->nullable()->unique()->after('password')->comment('微信 openid，预留');
            $table->timestamp('phone_verified_at')->nullable()->after('openid')->comment('手机号验证时间，预留短信');
        });

        Schema::table('merchants', function (Blueprint $table) {
            $table->string('password')->nullable()->after('contact_phone')->comment('bcrypt 单向哈希，不可还原');
        });
    }

    public function down(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->dropColumn('password');
        });

        Schema::table('xcx_users', function (Blueprint $table) {
            $table->dropColumn(['password', 'openid', 'phone_verified_at']);
        });
    }
};
