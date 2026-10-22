<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 负一层等用负数（如 -1）；避免依赖 doctrine/dbal 的 ->change()
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE building_houses MODIFY floor SMALLINT NULL COMMENT '所在层数，负一层=-1'");
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE building_houses ALTER COLUMN floor TYPE SMALLINT USING floor::smallint');
        }
        // sqlite 本身无 unsigned，可跳过
    }

    public function down(): void
    {
        DB::table('building_houses')->where('floor', '<', 1)->update(['floor' => 1]);

        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE building_houses MODIFY floor SMALLINT UNSIGNED NULL COMMENT '所在层数'");
        }
    }
};
