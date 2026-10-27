<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('xcx_users', function (Blueprint $table) {
            if (! Schema::hasColumn('xcx_users', 'merchant_apply_enterprise_id')) {
                $table->foreignId('merchant_apply_enterprise_id')
                    ->nullable()
                    ->after('merchant_applied_at')
                    ->constrained('enterprises')
                    ->nullOnDelete()
                    ->comment('商户申请目标企业');
            }
            if (! Schema::hasColumn('xcx_users', 'merchant_apply_note')) {
                $table->string('merchant_apply_note', 500)
                    ->nullable()
                    ->after('merchant_apply_enterprise_id')
                    ->comment('商户申请备注');
            }
            if (! Schema::hasColumn('xcx_users', 'merchant_reject_reason')) {
                $table->string('merchant_reject_reason', 255)
                    ->nullable()
                    ->after('merchant_apply_note')
                    ->comment('商户申请拒绝原因');
            }
        });
    }

    public function down(): void
    {
        Schema::table('xcx_users', function (Blueprint $table) {
            if (Schema::hasColumn('xcx_users', 'merchant_apply_enterprise_id')) {
                $table->dropConstrainedForeignId('merchant_apply_enterprise_id');
            }
            if (Schema::hasColumn('xcx_users', 'merchant_apply_note')) {
                $table->dropColumn('merchant_apply_note');
            }
            if (Schema::hasColumn('xcx_users', 'merchant_reject_reason')) {
                $table->dropColumn('merchant_reject_reason');
            }
        });
    }
};
