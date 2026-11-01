<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_fee_transactions', function (Blueprint $table): void {
            $table->unsignedSmallInteger('duration_months')->default(12)->after('duration_years');
            $table->string('package_type', 30)->default('yearly')->after('duration_months');
        });

        DB::table('student_fee_transactions')->orderBy('id')->get()->each(function ($transaction): void {
            DB::table('student_fee_transactions')->where('id', $transaction->id)->update([
                'duration_months' => ((int) $transaction->duration_years) * 12,
                'package_type' => (int) $transaction->duration_years >= 2 ? 'two_years' : 'yearly',
            ]);
        });

        Schema::table('student_fee_payments', function (Blueprint $table): void {
            $table->date('due_date')->nullable()->after('amount');
        });

        DB::table('student_fee_payments')
            ->join('student_fee_transactions', 'student_fee_transactions.id', '=', 'student_fee_payments.student_fee_transaction_id')
            ->select('student_fee_payments.id', 'student_fee_payments.installment_number', 'student_fee_transactions.period_starts_at')
            ->orderBy('student_fee_payments.id')
            ->get()
            ->each(function ($payment): void {
                $dueDate = \Illuminate\Support\Carbon::parse($payment->period_starts_at)
                    ->addMonths(((int) $payment->installment_number) - 1)
                    ->toDateString();

                DB::table('student_fee_payments')->where('id', $payment->id)->update(['due_date' => $dueDate]);
            });
    }

    public function down(): void
    {
        Schema::table('student_fee_payments', function (Blueprint $table): void {
            $table->dropColumn('due_date');
        });

        Schema::table('student_fee_transactions', function (Blueprint $table): void {
            $table->dropColumn(['duration_months', 'package_type']);
        });
    }
};
