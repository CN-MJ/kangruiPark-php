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
            $table->string('merchant_apply_status', 20)
                ->default('none')
                ->after('role')
                ->comment('none=无申请 pending=待审核 approved=已通过 rejected=已拒绝');
            $table->timestamp('merchant_applied_at')->nullable()->after('checked_in_at');
            $table->index('merchant_apply_status');
        });

        // 已是商户的账号视为已审核通过
        DB::table('xcx_users')
            ->where('role', 'merchant')
            ->update(['merchant_apply_status' => 'approved']);
    }

    public function down(): void
    {
        Schema::table('xcx_users', function (Blueprint $table) {
            $table->dropIndex(['merchant_apply_status']);
            $table->dropColumn(['merchant_apply_status', 'merchant_applied_at']);
        });
    }
};
