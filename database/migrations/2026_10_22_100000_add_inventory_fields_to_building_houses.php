<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('building_houses', function (Blueprint $table) {
            $table->string('building_no', 20)->nullable()->after('title')->comment('栋号');
            $table->unsignedSmallInteger('floor')->nullable()->after('building_no')->comment('所在层数');
            $table->decimal('floor_height', 4, 2)->nullable()->after('floor')->comment('层高(米)');
            $table->string('room_no', 30)->nullable()->after('floor_height')->comment('房号');
            $table->decimal('building_area', 10, 2)->nullable()->after('room_no')->comment('建筑面积㎡');
            $table->string('lease_sale_status', 32)
                ->default('available')
                ->after('building_area')
                ->comment('租售状态:available/rented_sold/rented_unsold/sold_unrented');

            $table->index(['building_no', 'floor', 'room_no'], 'bh_building_floor_room_idx');
            $table->index('lease_sale_status', 'bh_lease_sale_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('building_houses', function (Blueprint $table) {
            $table->dropIndex('bh_building_floor_room_idx');
            $table->dropIndex('bh_lease_sale_status_idx');
            $table->dropColumn([
                'building_no',
                'floor',
                'floor_height',
                'room_no',
                'building_area',
                'lease_sale_status',
            ]);
        });
    }
};
