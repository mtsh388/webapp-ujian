<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // The required billing columns and nullable registration payment link
        // are already created by earlier migrations:
        // - 2026_10_29_000003_make_registration_payment_optional_for_tuition_transactions
        // - 2026_11_01_000001_add_semester_packages_and_monthly_due_dates
    }

    public function down(): void
    {
        // Intentionally empty. Earlier migrations own these schema changes.
    }
};
