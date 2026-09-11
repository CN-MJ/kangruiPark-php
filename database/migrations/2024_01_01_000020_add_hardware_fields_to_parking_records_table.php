<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parking_records', function (Blueprint $table) {
            $table->tinyInteger('source_type')->default(0)->after('status')->comment('0=手动,1=硬件识别');
            $table->foreignId('event_id')->nullable()->after('source_type')->constrained('parking_events')->nullOnDelete();
            $table->foreignId('entry_device_id')->nullable()->after('event_id')->constrained('parking_devices')->nullOnDelete();
            $table->foreignId('exit_device_id')->nullable()->after('entry_device_id')->constrained('parking_devices')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('parking_records', function (Blueprint $table) {
            $table->dropConstrainedForeignId('exit_device_id');
            $table->dropConstrainedForeignId('entry_device_id');
            $table->dropConstrainedForeignId('event_id');
            $table->dropColumn('source_type');
        });
    }
};
