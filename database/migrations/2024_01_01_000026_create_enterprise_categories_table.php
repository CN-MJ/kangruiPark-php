<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enterprise_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id')->nullable()->comment('空=一级大类');
            $table->string('name', 80);
            $table->unsignedInteger('sort_order')->default(0);
            $table->tinyInteger('status')->default(1)->comment('1=启用, 0=停用');
            $table->timestamps();

            $table->index('parent_id');
            $table->index(['status', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enterprise_categories');
    }
};
