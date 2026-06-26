<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained('activities')->cascadeOnDelete();
            $table->string('contact_name', 50)->comment('报名人');
            $table->string('contact_phone', 20)->comment('手机号');
            $table->timestamps();

            $table->unique(['activity_id', 'contact_phone']);
            $table->index('contact_phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_registrations');
    }
};
