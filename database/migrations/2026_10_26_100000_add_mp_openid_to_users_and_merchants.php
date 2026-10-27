<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('xcx_users', function (Blueprint $table) {
            if (! Schema::hasColumn('xcx_users', 'mp_openid')) {
                $table->string('mp_openid', 64)->nullable()->after('openid')->comment('微信服务号 OpenID');
                $table->index('mp_openid');
            }
        });

        Schema::table('merchants', function (Blueprint $table) {
            if (! Schema::hasColumn('merchants', 'mp_openid')) {
                $table->string('mp_openid', 64)->nullable()->after('contact_phone')->comment('微信服务号 OpenID');
                $table->index('mp_openid');
            }
        });
    }

    public function down(): void
    {
        Schema::table('xcx_users', function (Blueprint $table) {
            if (Schema::hasColumn('xcx_users', 'mp_openid')) {
                $table->dropColumn('mp_openid');
            }
        });

        Schema::table('merchants', function (Blueprint $table) {
            if (Schema::hasColumn('merchants', 'mp_openid')) {
                $table->dropColumn('mp_openid');
            }
        });
    }
};
