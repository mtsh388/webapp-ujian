<?php

namespace App\Providers;

use App\Repositories\ExamAttemptRepositoryInterface;
use App\Repositories\ExamAttemptRepository;
use App\Repositories\ExamRepository;
use App\Repositories\ExamRepositoryInterface;
use App\Repositories\QuestionBankRepository;
use App\Repositories\QuestionBankRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            QuestionBankRepositoryInterface::class,
            QuestionBankRepository::class
        );

        // Binding untuk Exam
        $this->app->bind(
            ExamRepositoryInterface::class,
            ExamRepository::class
        );
        $this->app->bind(
            ExamAttemptRepositoryInterface::class,
            ExamAttemptRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
