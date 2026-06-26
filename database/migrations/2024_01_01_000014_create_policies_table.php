<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('policies', function (Blueprint $table) {
            $table->id();
            $table->string('title', 180)->comment('政策名称');
            $table->string('level', 20)->comment('级别');
            $table->longText('content')->comment('详细内容');
            $table->string('cover_image', 500)->nullable()->comment('封面图');
            $table->unsignedInteger('sort_order')->default(0)->comment('排序');
            $table->boolean('is_show')->default(true)->comment('是否显示');
            $table->timestamps();

            $table->index('level');
            $table->index('sort_order');
            $table->index('is_show');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('policies');
    }
};
