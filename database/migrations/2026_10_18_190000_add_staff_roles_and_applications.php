<?php

use App\Support\ParkPermissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 岗位权限落库，并给普通用户增加「申请成为工作人员」。
 * 已有园区账号：岗位为空或不在内置岗位里的，按原先逻辑记为管理员，避免升级后丢权限。
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('park_roles')) {
            Schema::create('park_roles', function (Blueprint $table) {
                $table->id();
                $table->string('key', 32)->unique();
                $table->string('name', 50);
                $table->json('permissions');
                $table->boolean('is_system')->default(false);
                $table->unsignedInteger('sort')->default(0);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('park_roles') && DB::table('park_roles')->count() === 0) {
            $now = now();
            foreach (ParkPermissions::defaultRoleRows() as $row) {
                DB::table('park_roles')->insert([
                    'key' => $row['key'],
                    'name' => $row['name'],
                    'permissions' => json_encode($row['permissions'], JSON_UNESCAPED_UNICODE),
                    'is_system' => true,
                    'sort' => $row['sort'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        if (Schema::hasTable('xcx_users') && ! Schema::hasColumn('xcx_users', 'staff_apply_status')) {
            Schema::table('xcx_users', function (Blueprint $table) {
                $table->string('staff_apply_status', 20)->default('none')->after('park_role')->index();
                $table->timestamp('staff_applied_at')->nullable()->after('staff_apply_status');
                $table->string('staff_apply_note', 200)->nullable()->after('staff_applied_at');
                $table->timestamp('staff_reviewed_at')->nullable()->after('staff_apply_note');
                $table->string('staff_reject_reason', 200)->nullable()->after('staff_reviewed_at');
            });
        }

        if (Schema::hasTable('xcx_users') && Schema::hasColumn('xcx_users', 'park_role')) {
            $known = array_column(ParkPermissions::defaultRoleRows(), 'key');
            DB::table('xcx_users')
                ->where('role', 'park')
                ->where(function ($query) use ($known) {
                    $query->whereNull('park_role')
                        ->orWhere('park_role', '')
                        ->orWhereNotIn('park_role', $known);
                })
                ->update(['park_role' => 'admin']);

            DB::table('xcx_users')
                ->where('role', '!=', 'park')
                ->whereNotNull('park_role')
                ->where('park_role', '!=', '')
                ->update(['park_role' => null]);
        }

        Cache::forget(ParkPermissions::CACHE_KEY);
    }

    public function down(): void
    {
        if (Schema::hasTable('xcx_users') && Schema::hasColumn('xcx_users', 'staff_apply_status')) {
            Schema::table('xcx_users', function (Blueprint $table) {
                $table->dropIndex(['staff_apply_status']);
                $table->dropColumn([
                    'staff_apply_status',
                    'staff_applied_at',
                    'staff_apply_note',
                    'staff_reviewed_at',
                    'staff_reject_reason',
                ]);
            });
        }

        Schema::dropIfExists('park_roles');
        Cache::forget(ParkPermissions::CACHE_KEY);
    }
};
