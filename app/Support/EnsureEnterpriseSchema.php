<?php

namespace App\Support;

use App\Models\Enterprise;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EnsureEnterpriseSchema
{
    /**
     * 确保企业库相关表存在（本地/服务器未 migrate 时可自愈）
     */
    public static function run(): void
    {
        if (! Schema::hasTable('enterprise_categories')) {
            Schema::create('enterprise_categories', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('parent_id')->nullable()->comment('空=一级大类');
                $table->string('name', 100);
                $table->unsignedInteger('sort_order')->default(0);
                $table->tinyInteger('status')->default(1)->comment('1=启用, 0=停用');
                $table->timestamps();
                $table->index('parent_id');
                $table->index(['status', 'sort_order']);
            });
        } else {
            if (! Schema::hasColumn('enterprise_categories', 'parent_id')) {
                Schema::table('enterprise_categories', function (Blueprint $table) {
                    $table->unsignedBigInteger('parent_id')->nullable()->after('id');
                    $table->index('parent_id');
                });
            }
            if (Schema::hasColumn('enterprise_categories', 'kind')) {
                Schema::table('enterprise_categories', function (Blueprint $table) {
                    try {
                        $table->dropIndex(['kind', 'status']);
                    } catch (\Throwable $e) {
                    }
                    $table->dropColumn('kind');
                });
            }
        }

        if (! Schema::hasTable('enterprises')) {
            Schema::create('enterprises', function (Blueprint $table) {
                $table->id();
                $table->string('name', 120);
                $table->string('contact_name', 50)->nullable();
                $table->string('contact_phone', 20)->nullable();
                $table->foreignId('primary_category_id')->constrained('enterprise_categories')->restrictOnDelete();
                $table->text('business_intro');
                $table->string('address', 255)->nullable();
                $table->string('district', 20)->nullable()->comment('赣州县区');
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->boolean('map_enabled')->default(false);
                $table->string('service_audience', 10)->default('bc');
                $table->string('source', 20)->default('community');
                $table->boolean('settle_in_park')->default(false);
                $table->json('images')->nullable();
                $table->tinyInteger('status')->default(1);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->index('status');
                $table->index('primary_category_id');
                $table->index('service_audience');
                $table->index('source');
                $table->index('settle_in_park');
                $table->index('district');
            });
        } elseif (! Schema::hasColumn('enterprises', 'district')) {
            Schema::table('enterprises', function (Blueprint $table) {
                $table->string('district', 20)->nullable()->after('address')->comment('赣州县区');
                $table->index('district');
            });

            if (class_exists(Enterprise::class)) {
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
        }

        if (! Schema::hasTable('enterprise_category')) {
            Schema::create('enterprise_category', function (Blueprint $table) {
                $table->id();
                $table->foreignId('enterprise_id')->constrained('enterprises')->cascadeOnDelete();
                $table->foreignId('enterprise_category_id')->constrained('enterprise_categories')->cascadeOnDelete();
                $table->unique(['enterprise_id', 'enterprise_category_id'], 'enterprise_category_unique');
            });
        }
    }
}
