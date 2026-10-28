<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\Student;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


Schedule::call(function (): void {
    Student::query()
        ->with('user')
        ->where('is_active', true)
        ->whereNotNull('access_expires_at')
        ->whereDate('access_expires_at', '<', today())
        ->chunkById(100, function ($students): void {
            foreach ($students as $student) {
                $student->forceFill(['is_active' => false])->save();

                if ($student->user) {
                    $student->user->forceFill(['is_active' => false])->save();
                }
            }
        });
})->dailyAt('00:10')->name('deactivate-expired-student-access')->withoutOverlapping();
