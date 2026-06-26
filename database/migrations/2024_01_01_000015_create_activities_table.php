<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->string('title', 180);
            $table->string('cover_image', 500)->nullable();
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->string('location', 120);
            $table->text('description');
            $table->unsignedInteger('max_attendees')->default(0);
            $table->unsignedInteger('registered_count')->default(0);
            $table->tinyInteger('status')->default(0)->comment('0报名中,1已结束,2已取消');
            $table->timestamps();

            $table->index('status');
            $table->index('start_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
