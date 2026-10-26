<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('registration_type', 20)->nullable()->after('user_id')->index();
        });

        DB::table('students')
            ->whereIn('id', function ($query) {
                $query->select('student_id')
                    ->from('registration_payments')
                    ->where('payment_type', 'registration')
                    ->whereNotNull('student_id');
            })
            ->update(['registration_type' => 'paid']);
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex(['registration_type']);
            $table->dropColumn('registration_type');
        });
    }
};
