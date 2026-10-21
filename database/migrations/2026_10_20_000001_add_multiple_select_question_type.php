<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE question_banks MODIFY type ENUM('multiple_choice', 'multiple_select', 'true_false') NOT NULL");
            DB::statement("ALTER TABLE exam_questions MODIFY type ENUM('multiple_choice', 'multiple_select', 'true_false') NOT NULL");
        }

        Schema::table('exam_answers', function (Blueprint $table) {
            $table->json('selected_option_ids')->nullable()->after('selected_option_id');
        });
    }

    public function down(): void
    {
        Schema::table('exam_answers', function (Blueprint $table) {
            $table->dropColumn('selected_option_ids');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE question_banks MODIFY type ENUM('multiple_choice', 'true_false') NOT NULL");
            DB::statement("ALTER TABLE exam_questions MODIFY type ENUM('multiple_choice', 'true_false') NOT NULL");
        }
    }
};
