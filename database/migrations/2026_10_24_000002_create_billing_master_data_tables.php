<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('billing_fee_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name', 120)->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->string('name', 40)->unique();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('billing_fee_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_type_id')->constrained('billing_fee_types')->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->restrictOnDelete();
            $table->foreignId('class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->enum('period_type', ['semester', 'year']);
            $table->string('period_label', 80);
            $table->decimal('amount', 14, 2);
            $table->unsignedTinyInteger('due_day')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['academic_year_id', 'class_id', 'is_active']);
        });

        Schema::create('billing_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('bank_name', 100);
            $table->string('account_name', 120);
            $table->string('account_number', 80);
            $table->string('branch', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['bank_name', 'account_number']);
        });

        Schema::table('billing_invoices', function (Blueprint $table) {
            $table->foreignId('fee_rate_id')->nullable()->after('student_id')
                ->constrained('billing_fee_rates')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('billing_invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('fee_rate_id');
        });
        Schema::dropIfExists('billing_bank_accounts');
        Schema::dropIfExists('billing_fee_rates');
        Schema::dropIfExists('academic_years');
        Schema::dropIfExists('billing_fee_types');
    }
};
