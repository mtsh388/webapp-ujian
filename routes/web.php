<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ClassRoomController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\ParentController;
use App\Http\Controllers\QuestionBankController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\ExamAttemptController;
use App\Http\Controllers\ExamViolationController;
use App\Http\Controllers\ResultController;
use App\Http\Controllers\RegistrationFeeController;
use App\Models\Exam;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth', 'password.first', 'profile.completed'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('permission:dashboard.view')
        ->name('dashboard');

    Route::prefix('master-data')->group(function () {

        Route::prefix('kelas')
            ->middleware('permission:class.view')
            ->group(function () {
                Route::get('/', [ClassRoomController::class, 'index'])
                    ->name('classes.index');

                Route::get('/data', [ClassRoomController::class, 'data'])
                    ->name('classes.data');

                Route::get('/create', [ClassRoomController::class, 'create'])
                    ->middleware('permission:class.create')
                    ->name('classes.create');
                Route::patch('/{classRoom}/status', [ClassRoomController::class, 'updateStatus'])->middleware('permission:class.update')->name('classes.status');
                Route::post('/', [ClassRoomController::class, 'store'])
                    ->middleware('permission:class.create')
                    ->name('classes.store');

                Route::get('/{classRoom}/edit', [ClassRoomController::class, 'edit'])
                    ->middleware('permission:class.update')
                    ->name('classes.edit');

                Route::put('/{classRoom}', [ClassRoomController::class, 'update'])
                    ->middleware('permission:class.update')
                    ->name('classes.update');

                Route::get('/{classRoom}', [ClassRoomController::class, 'show'])
                    ->name('classes.show');

                Route::delete('/{classRoom}', [ClassRoomController::class, 'destroy'])
                    ->middleware('permission:class.delete')
                    ->name('classes.destroy');
            });

        Route::prefix('mata-pelajaran')
            ->middleware('permission:subject.view')
            ->group(function () {
                Route::get('/', [SubjectController::class, 'index'])
                    ->name('subjects.index');

                Route::get('/data', [SubjectController::class, 'data'])
                    ->name('subjects.data');

                Route::get('/create', [SubjectController::class, 'create'])
                    ->middleware('permission:subject.create')
                    ->name('subjects.create');

                Route::post('/', [SubjectController::class, 'store'])
                    ->middleware('permission:subject.create')
                    ->name('subjects.store');

                Route::patch('/{subject}/status', [SubjectController::class, 'updateStatus'])
                    ->middleware('permission:subject.update')
                    ->name('subjects.status');

                Route::get('/{subject}/edit', [SubjectController::class, 'edit'])
                    ->middleware('permission:subject.update')
                    ->name('subjects.edit');

                Route::put('/{subject}', [SubjectController::class, 'update'])
                    ->middleware('permission:subject.update')
                    ->name('subjects.update');

                Route::get('/{subject}', [SubjectController::class, 'show'])
                    ->name('subjects.show');

                Route::delete('/{subject}', [SubjectController::class, 'destroy'])
                    ->middleware('permission:subject.delete')
                    ->name('subjects.destroy');
            });

        Route::prefix('siswa')
            ->middleware('permission:student.view')
            ->group(function () {

                Route::get('/', [StudentController::class, 'index'])
                    ->name('students.index');

                Route::get('/data', [StudentController::class, 'data'])
                    ->name('students.data');

                Route::get('/create', [StudentController::class, 'create'])
                    ->middleware('permission:student.create')
                    ->name('students.create');

                Route::post('/', [StudentController::class, 'store'])
                    ->middleware('permission:student.create')
                    ->name('students.store');

                Route::patch('/{student}/status', [StudentController::class, 'updateStatus'])
                    ->middleware('permission:student.update')
                    ->name('students.status');

                Route::patch('/{student}/account-status', [StudentController::class, 'updateAccountStatus'])
                    ->middleware('permission:student.update')
                    ->name('students.account-status');

                Route::get('/{student}/edit', [StudentController::class, 'edit'])
                    ->middleware('permission:student.update')
                    ->name('students.edit');

                Route::put('/{student}', [StudentController::class, 'update'])
                    ->middleware('permission:student.update')
                    ->name('students.update');

                Route::get('/{student}', [StudentController::class, 'show'])
                    ->name('students.show');

                Route::delete('/{student}', [StudentController::class, 'destroy'])
                    ->middleware('permission:student.delete')
                    ->name('students.destroy');
            });

        Route::prefix('orang-tua')
            ->middleware('permission:parent.view')
            ->group(function () {
                Route::get('/', [ParentController::class, 'index'])
                    ->name('parents.index');

                Route::get('/data', [ParentController::class, 'data'])
                    ->name('parents.data');

                Route::get('/create', [ParentController::class, 'create'])
                    ->middleware('permission:parent.create')
                    ->name('parents.create');

                Route::post('/', [ParentController::class, 'store'])
                    ->middleware('permission:parent.create')
                    ->name('parents.store');

                Route::patch('/{parent}/status', [ParentController::class, 'updateStatus'])
                    ->middleware('permission:parent.update')
                    ->name('parents.status');

                Route::get('/{parent}/edit', [ParentController::class, 'edit'])
                    ->middleware('permission:parent.update')
                    ->name('parents.edit');

                Route::put('/{parent}', [ParentController::class, 'update'])
                    ->middleware('permission:parent.update')
                    ->name('parents.update');

                Route::delete('/{parent}', [ParentController::class, 'destroy'])
                    ->middleware('permission:parent.delete')
                    ->name('parents.destroy');

                Route::get('/{parent}', [ParentController::class, 'show'])
                    ->name('parents.show');
            });

    });

    Route::prefix('master-data/biaya-pendaftaran')
        ->middleware('permission:registration-fee.view')
        ->group(function () {
            Route::get('/', [RegistrationFeeController::class, 'index'])->name('registration-fees.index');
            Route::get('/create', [RegistrationFeeController::class, 'create'])
                ->middleware('permission:registration-fee.create')
                ->name('registration-fees.create');
            Route::post('/', [RegistrationFeeController::class, 'store'])
                ->middleware('permission:registration-fee.create')
                ->name('registration-fees.store');
            Route::get('/{registrationFee}/edit', [RegistrationFeeController::class, 'edit'])
                ->middleware('permission:registration-fee.update')
                ->name('registration-fees.edit');
            Route::put('/{registrationFee}', [RegistrationFeeController::class, 'update'])
                ->middleware('permission:registration-fee.update')
                ->name('registration-fees.update');
            Route::delete('/{registrationFee}', [RegistrationFeeController::class, 'destroy'])
                ->middleware('permission:registration-fee.delete')
                ->name('registration-fees.destroy');
        });

    Route::prefix('bank-soal')
        ->middleware('permission:question.view')
        ->group(function () {
            Route::get('/', [QuestionBankController::class, 'index'])
                ->name('questions.index');
            Route::get('/data', [QuestionBankController::class, 'data'])->name('questions.data');
            Route::get('/import/template', [QuestionBankController::class, 'importTemplate'])
                ->middleware('permission:question.create')
                ->name('questions.import.template');


            Route::get('/create', [QuestionBankController::class, 'create'])
                ->middleware('permission:question.create')
                ->name('questions.create');

            Route::post('/', [QuestionBankController::class, 'store'])
                ->middleware('permission:question.create')
                ->name('questions.store');

            Route::get('/import', [QuestionBankController::class, 'import'])
                ->middleware('permission:question.create')
                ->name('questions.import');

            Route::post('/import/preview', [QuestionBankController::class, 'importPreview'])
                ->middleware('permission:question.create')
                ->name('questions.import.preview');

            Route::post('/import/confirm', [QuestionBankController::class, 'importConfirm'])
                ->middleware('permission:question.create')
                ->name('questions.import.confirm');

            Route::delete('/media/{media}', [QuestionBankController::class, 'destroyMedia'])
                ->middleware('permission:question.update')
                ->name('questions.media.destroy');

            Route::delete('/option-media/{media}', [QuestionBankController::class, 'destroyOptionMedia'])
                ->middleware('permission:question.update')
                ->name('questions.option-media.destroy');

            Route::get('/{question}/edit', [QuestionBankController::class, 'edit'])
                ->middleware('permission:question.update')
                ->name('questions.edit');

            Route::put('/{question}', [QuestionBankController::class, 'update'])
                ->middleware('permission:question.update')
                ->name('questions.update');

            Route::delete('/bulk', [QuestionBankController::class, 'bulkDestroy'])
                ->middleware('permission:question.delete')
                ->name('questions.bulk-destroy');

            Route::delete('/{question}', [QuestionBankController::class, 'destroy'])
                ->middleware('permission:question.delete')
                ->name('questions.destroy');

            Route::get('/{question}', [QuestionBankController::class, 'show'])
                ->name('questions.show');
        });

    Route::get('/pengawasan/pelanggaran/data', [ExamViolationController::class, 'data'])
        ->middleware('permission:exam.violation.view')
        ->name('proctoring.data');

    Route::get('/pengawasan/pelanggaran', [ExamViolationController::class, 'index'])
        ->middleware('permission:exam.violation.view')
        ->name('proctoring.index');

    Route::prefix('ujian')->group(function () {

        // 1. RUTE STATIS (Harus paling atas)
        Route::middleware(['role:Admin|Tentor', 'permission:exam.view'])->group(function () {
            Route::get('/', [ExamController::class, 'index'])->name('exams.index');
            Route::get('/data', [ExamController::class, 'data'])->name('exams.data');
        });

        Route::get('/tersedia', [ExamController::class, 'available'])
            ->middleware('permission:exam.take')
            ->name('exams.available');

        Route::get('/create', [ExamController::class, 'create'])->middleware('permission:exam.create')->name('exams.create');
        Route::post('/', [ExamController::class, 'store'])->middleware('permission:exam.create')->name('exams.store');

        // 2. RUTE DINAMIS dengan parameter {exam} (Di bawah rute statis)

        Route::middleware('permission:exam.update')->group(function () {
            Route::get('/{exam}/edit', [ExamController::class, 'edit'])
                ->name('exams.edit');

            Route::put('/{exam}', [ExamController::class, 'update'])
                ->name('exams.update');

            Route::get('/{exam}/soal', [ExamController::class, 'questions'])
                ->name('exams.questions');

            Route::post('/{exam}/soal', [ExamController::class, 'storeQuestions'])
                ->name('exams.questions.store');

            Route::delete('/{exam}/soal/{questionId}', [ExamController::class, 'destroyQuestion'])
                ->name('exams.questions.destroy');

            Route::get('/{exam}/peserta', [ExamController::class, 'participants'])
                ->name('exams.participants');

            Route::post('/{exam}/peserta', [ExamController::class, 'storeParticipants'])
                ->name('exams.participants.store');

            Route::patch('/{exam}/peserta/{participant}/status', [ExamController::class, 'updateParticipantStatus'])
                ->name('exams.participants.status');

            Route::delete('/{exam}/peserta/{participant}', [ExamController::class, 'destroyParticipant'])
                ->name('exams.participants.destroy');
        });


        Route::get('/{exam}', [ExamController::class, 'show'])
            ->middleware(['role:Admin|Tentor', 'permission:exam.view'])
            ->name('exams.show');

        Route::delete('/{exam}', [ExamController::class, 'destroy'])->middleware('permission:exam.delete')->name('exams.destroy');
        Route::middleware('role:Admin|Tentor')->group(function () {
            Route::post('/{exam}/publish', [ExamController::class, 'publish'])
                ->middleware('permission:exam.publish')
                ->name('exams.publish');

            Route::post('/{exam}/draft', [ExamController::class, 'draft'])
                ->middleware('permission:exam.update')
                ->name('exams.draft');

            Route::post('/{exam}/close', [ExamController::class, 'close'])
                ->middleware('permission:exam.close')
                ->name('exams.close');
        });

        Route::middleware('permission:exam.take')->group(function () {
            Route::post('/{exam}/mulai', [ExamAttemptController::class, 'start'])
                ->middleware('throttle:10,1')
                ->name('exams.start');

            Route::get('/{exam}/attempt/{attempt}', [ExamAttemptController::class, 'show'])
                ->name('exams.attempt');

            Route::post('/{exam}/attempt/{attempt}/answer', [ExamAttemptController::class, 'answer'])
                ->name('exams.answer');

            Route::post('/{exam}/attempt/{attempt}/submit', [ExamAttemptController::class, 'submit'])
                ->name('exams.submit');

            Route::post('/{exam}/attempt/{attempt}/violations', [ExamViolationController::class, 'store'])
                ->middleware('throttle:30,1')
                ->name('exams.violations.store');
        });
    });

    Route::prefix('hasil-ujian')->group(function () {

        Route::get('/', [ResultController::class, 'index'])
            ->middleware('permission:result.view')
            ->name('results.index');

        Route::get('/data', [ResultController::class, 'data'])
            ->middleware('permission:result.view')
            ->name('results.data');

        Route::get('/ujian/{exam}/ranking/data', [ResultController::class, 'rankingData'])
            ->name('results.ranking.data');

        Route::get('/ujian/{exam}/ranking', [ResultController::class, 'ranking'])
            ->name('results.ranking');

        Route::get('/saya', [ResultController::class, 'myResults'])
            ->middleware('permission:result.view_own')
            ->name('results.my');

        Route::get('/saya/{attempt}', [ResultController::class, 'myResult'])
            ->middleware('permission:result.view_own')
            ->name('results.my.show');

        Route::get('/anak', [ResultController::class, 'childrenResults'])
            ->middleware('permission:result.view_child')
            ->name('results.children');

        Route::get('/anak/{attempt}', [ResultController::class, 'childResult'])
            ->middleware('permission:result.view_child')
            ->name('results.children.show');

        Route::get('/{attempt}/pdf', [ResultController::class, 'pdf'])
            ->middleware('permission:result.view_own')
            ->name('results.pdf');

        Route::get('/{attempt}', [ResultController::class, 'show'])
            ->middleware('permission:result.view')
            ->name('results.show');
    });

});

require __DIR__ . '/auth.php';
