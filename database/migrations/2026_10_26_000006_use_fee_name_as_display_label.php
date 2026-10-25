<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('registration_fees')
            ->whereNotNull('name')
            ->update(['fee_type_label' => DB::raw('name')]);
    }

    public function down(): void
    {
    }
};
