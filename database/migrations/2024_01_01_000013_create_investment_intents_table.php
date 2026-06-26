<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investment_intents', function (Blueprint $table) {
            $table->id();
            $table->string('company_name', 120)->comment('公司名称');
            $table->string('contact_name', 50)->comment('联系人');
            $table->string('contact_phone', 20)->comment('手机号');
            $table->string('demand_area', 50)->comment('需求面积');
            $table->foreignId('house_id')->nullable()->constrained('building_houses')->nullOnDelete();
            $table->string('message', 500)->nullable()->comment('备注');
            $table->tinyInteger('status')->default(0)->comment('状态:0待联系,1已联系,2意向强,3已成交,4无效');
            $table->string('source', 30)->default('小程序')->comment('来源');
            $table->timestamps();

            $table->index('status');
            $table->index('source');
            $table->index('contact_phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investment_intents');
    }
};
