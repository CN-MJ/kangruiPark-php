<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 新闻表
     */
    public function up(): void
    {
        Schema::create('news', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200)->comment('标题');
            $table->text('content')->comment('正文');
            $table->string('cover_image', 500)->nullable()->comment('封面图 URL');
            $table->dateTime('publish_time')->comment('发布时间');
            $table->boolean('is_top')->default(false)->comment('是否置顶');
            $table->timestamps();

            $table->index('publish_time');
            $table->index('is_top');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news');
    }
};
