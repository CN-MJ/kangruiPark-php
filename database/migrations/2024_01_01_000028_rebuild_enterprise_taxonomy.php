<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 先断外键，再重建（兼容 027 已创建的新表）
        Schema::dropIfExists('enterprise_category');

        if (Schema::hasTable('enterprises')) {
            Schema::dropIfExists('enterprises');
        }

        if (Schema::hasTable('enterprise_categories')) {
            // 已是新结构（含 parent_id）则只保证企业表存在，避免重复破坏
            if (Schema::hasColumn('enterprise_categories', 'parent_id') && ! Schema::hasColumn('enterprise_categories', 'kind')) {
                if (! Schema::hasTable('enterprises')) {
                    $this->createEnterprisesTable();
                }
                if (! Schema::hasTable('enterprise_category')) {
                    $this->createPivotTable();
                }

                return;
            }

            Schema::table('enterprise_categories', function (Blueprint $table) {
                if (Schema::hasColumn('enterprise_categories', 'kind')) {
                    try {
                        $table->dropIndex(['kind', 'status']);
                    } catch (\Throwable $e) {
                    }
                    $table->dropColumn('kind');
                }
                if (! Schema::hasColumn('enterprise_categories', 'parent_id')) {
                    $table->unsignedBigInteger('parent_id')->nullable()->after('id')->comment('空=一级大类');
                    $table->index('parent_id');
                }
            });
            DB::table('enterprise_categories')->delete();
        } else {
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
        }

        $this->createEnterprisesTable();
        $this->createPivotTable();
    }

    private function createEnterprisesTable(): void
    {
        Schema::create('enterprises', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120)->comment('企业名称');
            $table->string('contact_name', 50)->nullable()->comment('联系人');
            $table->string('contact_phone', 20)->nullable()->comment('联系电话');
            $table->foreignId('primary_category_id')->constrained('enterprise_categories')->restrictOnDelete()->comment('主大类');
            $table->text('business_intro')->comment('主营业务简介');
            $table->string('address', 255)->nullable()->comment('地址');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('map_enabled')->default(false)->comment('是否支持地图定位');
            $table->string('service_audience', 10)->default('bc')->comment('c=百姓 / b=企业 / bc=兼顾');
            $table->string('source', 20)->default('community')->comment('community=健康共同体入库 / self=自主入驻');
            $table->boolean('settle_in_park')->default(false)->comment('是否入驻本园区');
            $table->json('images')->nullable()->comment('图片 URL，最多 5 张');
            $table->tinyInteger('status')->default(1)->comment('1=上架, 0=下架');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('primary_category_id');
            $table->index('service_audience');
            $table->index('source');
            $table->index('settle_in_park');
        });
    }

    private function createPivotTable(): void
    {
        Schema::create('enterprise_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enterprise_id')->constrained('enterprises')->cascadeOnDelete();
            $table->foreignId('enterprise_category_id')->constrained('enterprise_categories')->cascadeOnDelete();
            $table->unique(['enterprise_id', 'enterprise_category_id'], 'enterprise_category_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enterprise_category');
        Schema::dropIfExists('enterprises');

        if (Schema::hasColumn('enterprise_categories', 'parent_id')) {
            Schema::table('enterprise_categories', function (Blueprint $table) {
                $table->dropIndex(['parent_id']);
                $table->dropColumn('parent_id');
                $table->string('kind', 20)->default('subject')->after('id');
                $table->index(['kind', 'status']);
            });
        }
    }
};
