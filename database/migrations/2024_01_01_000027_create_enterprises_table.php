<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
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
            $table->unsignedBigInteger('created_by')->nullable()->comment('录入人');
            $table->timestamps();

            $table->index('status');
            $table->index('primary_category_id');
            $table->index('service_audience');
            $table->index('source');
            $table->index('settle_in_park');
        });

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
    }
};
