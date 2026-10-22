<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 兼容已执行过「仅 deleted_at」假删除的环境：补 is_delete，并按 deleted_at 回填。
 */
return new class extends Migration
{
    /** @var list<string> */
    private array $tables = [
        'xcx_users',
        'merchants',
        'enterprises',
        'enterprise_categories',
        'news',
        'policies',
        'activities',
        'activity_registrations',
        'building_houses',
        'investment_intents',
        'service_teams',
        'appointments',
        'user_vehicles',
        'park_roles',
        'parking_records',
        'parking_devices',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            if (! Schema::hasColumn($table, 'is_delete')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->unsignedTinyInteger('is_delete')->default(0)->index()->comment('0正常 1已删除');
                });
            }

            if (! Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->timestamp('deleted_at')->nullable()->index()->comment('最近一次删除时间');
                });
            }

            DB::table($table)->whereNotNull('deleted_at')->where('is_delete', 0)->update(['is_delete' => 1]);
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'is_delete')) {
                continue;
            }
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropColumn('is_delete');
            });
        }
    }
};
