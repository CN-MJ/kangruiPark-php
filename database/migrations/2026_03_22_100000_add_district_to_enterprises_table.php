<?php

use App\Models\Enterprise;
use App\Support\GanzhouDistrict;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('enterprises')) {
            return;
        }

        if (! Schema::hasColumn('enterprises', 'district')) {
            Schema::table('enterprises', function (Blueprint $table) {
                $table->string('district', 20)->nullable()->after('address')->comment('赣州县区');
                $table->index('district');
            });
        }

        Enterprise::query()->orderBy('id')->chunkById(100, function ($rows) {
            foreach ($rows as $enterprise) {
                $district = GanzhouDistrict::resolve(
                    $enterprise->address,
                    $enterprise->latitude,
                    $enterprise->longitude,
                );
                if ($district && $enterprise->district !== $district) {
                    $enterprise->district = $district;
                    $enterprise->saveQuietly();
                }
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('enterprises') && Schema::hasColumn('enterprises', 'district')) {
            Schema::table('enterprises', function (Blueprint $table) {
                $table->dropIndex(['district']);
                $table->dropColumn('district');
            });
        }
    }
};
