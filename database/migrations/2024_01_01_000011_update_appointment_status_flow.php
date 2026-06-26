<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('appointments')
            ->where('status', 1)
            ->update(['status' => 3]);
    }

    public function down(): void
    {
        DB::table('appointments')
            ->where('status', 3)
            ->update(['status' => 1]);
    }
};
