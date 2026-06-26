<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('building_houses', function (Blueprint $table) {
            $table->id();
            $table->string('title', 120)->comment('房源名称/楼栋号');
            $table->string('area', 50)->comment('面积');
            $table->string('price', 50)->comment('价格');
            $table->string('house_type', 50)->comment('户型');
            $table->text('description')->comment('详细描述');
            $table->json('images')->nullable()->comment('房源图片');
            $table->tinyInteger('status')->default(1)->comment('状态:1在售/在租,0已售/已租');
            $table->unsignedInteger('sort_order')->default(0)->comment('排序');
            $table->timestamps();

            $table->index('status');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('building_houses');
    }
};
