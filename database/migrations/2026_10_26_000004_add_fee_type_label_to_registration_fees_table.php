<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registration_fees', function (Blueprint $table): void {
            $table->string('fee_type_label', 100)->nullable()->after('fee_type');
        });

        $labels = [
            'registration' => 'Pendaftaran',
            'monthly' => 'SPP',
            'exam' => 'Ujian',
            'books' => 'Buku dan Modul',
            'uniform' => 'Seragam',
            'activity' => 'Kegiatan',
            'other' => 'Lainnya',
        ];

        foreach ($labels as $type => $label) {
            DB::table('registration_fees')->where('fee_type', $type)->update(['fee_type_label' => $label]);
        }

        DB::table('registration_fees')->whereNull('fee_type_label')->orderBy('id')->get(['id', 'fee_type'])->each(
            fn ($fee) => DB::table('registration_fees')->where('id', $fee->id)->update([
                'fee_type_label' => str($fee->fee_type)->replace('-', ' ')->title()->toString(),
            ])
        );
    }

    public function down(): void
    {
        Schema::table('registration_fees', function (Blueprint $table): void {
            $table->dropColumn('fee_type_label');
        });
    }
};
