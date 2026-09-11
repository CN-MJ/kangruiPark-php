<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 数据库层锁定：即使最高权限账号也无法 UPDATE/DELETE 审计日志。
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('admin_audit_logs')) {
            return;
        }

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::unprepared('DROP TRIGGER IF EXISTS admin_audit_logs_no_update');
        DB::unprepared('DROP TRIGGER IF EXISTS admin_audit_logs_no_delete');

        DB::unprepared(<<<'SQL'
CREATE TRIGGER admin_audit_logs_no_update
BEFORE UPDATE ON admin_audit_logs
FOR EACH ROW
SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = '日志不可修改'
SQL);

        DB::unprepared(<<<'SQL'
CREATE TRIGGER admin_audit_logs_no_delete
BEFORE DELETE ON admin_audit_logs
FOR EACH ROW
SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = '日志不可删除'
SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::unprepared('DROP TRIGGER IF EXISTS admin_audit_logs_no_update');
        DB::unprepared('DROP TRIGGER IF EXISTS admin_audit_logs_no_delete');
    }
};
