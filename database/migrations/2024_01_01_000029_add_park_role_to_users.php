<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('xcx_users', function (Blueprint $table) {
            if (! Schema::hasColumn('xcx_users', 'park_role')) {
                $table->string('park_role', 32)->nullable()->after('role');
            }
        });

        // 已有园区账号默认管理员，避免上线后菜单被裁空
        if (Schema::hasColumn('xcx_users', 'park_role')) {
            DB::table('xcx_users')
                ->where('role', 'park')
                ->where(function ($q) {
                    $q->whereNull('park_role')->orWhere('park_role', '');
                })
                ->update(['park_role' => 'admin']);
        }
    }

    public function down(): void
    {
        Schema::table('xcx_users', function (Blueprint $table) {
            if (Schema::hasColumn('xcx_users', 'park_role')) {
                $table->dropColumn('park_role');
            }
        });
    }
};
