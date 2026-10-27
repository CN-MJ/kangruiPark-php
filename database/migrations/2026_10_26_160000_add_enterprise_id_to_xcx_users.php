<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('xcx_users', function (Blueprint $table) {
            if (! Schema::hasColumn('xcx_users', 'enterprise_id')) {
                $table->foreignId('enterprise_id')
                    ->nullable()
                    ->after('role')
                    ->constrained('enterprises')
                    ->nullOnDelete()
                    ->comment('商户身份绑定的入驻企业');
            }
        });
    }

    public function down(): void
    {
        Schema::table('xcx_users', function (Blueprint $table) {
            if (Schema::hasColumn('xcx_users', 'enterprise_id')) {
                $table->dropConstrainedForeignId('enterprise_id');
            }
        });
    }
};
