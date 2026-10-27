<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enterprise_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enterprise_id')->constrained('enterprises')->cascadeOnDelete();
            $table->string('name', 120)->comment('服务名称');
            $table->string('summary', 255)->nullable()->comment('一句话简介');
            $table->text('description')->nullable()->comment('服务详情');
            $table->string('price_text', 80)->nullable()->comment('价格说明，如 面议/98元起');
            $table->tinyInteger('status')->default(1)->comment('1=上架 0=下架');
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedTinyInteger('is_delete')->default(0)->index()->comment('0正常 1已删除');
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();

            $table->index(['enterprise_id', 'status']);
            $table->index('sort_order');
        });

        Schema::table('merchants', function (Blueprint $table) {
            if (! Schema::hasColumn('merchants', 'enterprise_id')) {
                $table->foreignId('enterprise_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('enterprises')
                    ->nullOnDelete()
                    ->comment('关联产业库企业（店铺）');
            }
        });

        Schema::table('appointments', function (Blueprint $table) {
            if (! Schema::hasColumn('appointments', 'enterprise_id')) {
                $table->foreignId('enterprise_id')
                    ->nullable()
                    ->after('merchant_id')
                    ->constrained('enterprises')
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn('appointments', 'enterprise_service_id')) {
                $table->foreignId('enterprise_service_id')
                    ->nullable()
                    ->after('enterprise_id')
                    ->constrained('enterprise_services')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            if (Schema::hasColumn('appointments', 'enterprise_service_id')) {
                $table->dropConstrainedForeignId('enterprise_service_id');
            }
            if (Schema::hasColumn('appointments', 'enterprise_id')) {
                $table->dropConstrainedForeignId('enterprise_id');
            }
        });

        Schema::table('merchants', function (Blueprint $table) {
            if (Schema::hasColumn('merchants', 'enterprise_id')) {
                $table->dropConstrainedForeignId('enterprise_id');
            }
        });

        Schema::dropIfExists('enterprise_services');
    }
};
