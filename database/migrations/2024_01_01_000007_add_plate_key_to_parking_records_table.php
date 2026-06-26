<?php

use App\Models\ParkingRecord;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parking_records', function (Blueprint $table) {
            $table->string('plate_key', 20)->nullable()->after('plate_number')->comment('标准化车牌，用于去重');
            $table->string('active_plate_key', 20)->nullable()->after('plate_key')->comment('在场时唯一标识');
        });

        $this->backfillPlateKeys();

        Schema::table('parking_records', function (Blueprint $table) {
            $table->unique('active_plate_key', 'parking_records_active_plate_key_unique');
            $table->index('plate_key');
        });
    }

    public function down(): void
    {
        Schema::table('parking_records', function (Blueprint $table) {
            $table->dropUnique('parking_records_active_plate_key_unique');
            $table->dropIndex(['plate_key']);
            $table->dropColumn(['plate_key', 'active_plate_key']);
        });
    }

    private function backfillPlateKeys(): void
    {
        $inParkByKey = [];

        $records = DB::table('parking_records')
            ->orderBy('entry_time')
            ->orderBy('id')
            ->get();

        foreach ($records as $record) {
            $plateKey = ParkingRecord::normalizePlate($record->plate_number);

            $update = [
                'plate_key' => $plateKey,
                'plate_number' => $plateKey,
            ];

            if ((int) $record->status === ParkingRecord::STATUS_IN_PARK) {
                if (isset($inParkByKey[$plateKey])) {
                    $update['status'] = ParkingRecord::STATUS_LEFT;
                    $update['exit_time'] = now();
                    $update['active_plate_key'] = null;
                } else {
                    $inParkByKey[$plateKey] = $record->id;
                    $update['active_plate_key'] = $plateKey;
                }
            } else {
                $update['active_plate_key'] = null;
            }

            DB::table('parking_records')->where('id', $record->id)->update($update);
        }
    }
};
