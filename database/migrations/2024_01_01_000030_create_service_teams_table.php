<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_teams', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('title', 80)->comment('职务');
            $table->string('phone', 30)->nullable();
            $table->string('photo', 500)->nullable()->comment('证件照');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_show')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_teams');
    }
};
