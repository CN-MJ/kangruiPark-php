<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enterprise_services', function (Blueprint $table) {
            if (! Schema::hasColumn('enterprise_services', 'images')) {
                $table->json('images')->nullable()->after('price_text')->comment('服务图片，最多3张');
            }
        });
    }

    public function down(): void
    {
        Schema::table('enterprise_services', function (Blueprint $table) {
            if (Schema::hasColumn('enterprise_services', 'images')) {
                $table->dropColumn('images');
            }
        });
    }
};
