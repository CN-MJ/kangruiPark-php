<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 业务表假删除：is_delete 0=正常 1=已删除；deleted_at=最近一次删除时间。
 * 令牌、审计日志、设备事件等不走假删除。
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

            // 若此前只用 deleted_at 做假删除，补齐 is_delete
            if (Schema::hasColumn($table, 'is_delete') && Schema::hasColumn($table, 'deleted_at')) {
                DB::table($table)->whereNotNull('deleted_at')->where('is_delete', 0)->update(['is_delete' => 1]);
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                if (Schema::hasColumn($table, 'is_delete')) {
                    $blueprint->dropColumn('is_delete');
                }
                if (Schema::hasColumn($table, 'deleted_at')) {
                    $blueprint->dropColumn('deleted_at');
                }
            });
        }
    }
};
