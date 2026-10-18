<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('xcx_users', function (Blueprint $table) {
            if (! Schema::hasColumn('xcx_users', 'avatar')) {
                $table->string('avatar', 255)->nullable()->after('name')->comment('头像，public 磁盘相对路径 uploads/avatars/...');
            }
        });
    }

    public function down(): void
    {
        Schema::table('xcx_users', function (Blueprint $table) {
            if (Schema::hasColumn('xcx_users', 'avatar')) {
                $table->dropColumn('avatar');
            }
        });
    }
};
