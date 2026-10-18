<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\StudentProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);

    Route::get('/login', [AuthController::class, 'index'])
        ->name('login');

    Route::post('/login', [AuthController::class, 'login'])
        ->name('login.store');

    Route::get('/forgot-password', [AuthController::class, 'forgotPassword'])
        ->name('password.request');

    Route::post('/forgot-password', [AuthController::class, 'sendForgotPassword'])
        ->name('password.email');

    Route::get('/reset-password/{token}', [AuthController::class, 'resetPassword'])
        ->name('password.reset');

    Route::post('/reset-password', [AuthController::class, 'reset'])
        ->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/lengkapi-profil', [StudentProfileController::class, 'edit'])->name('students.profile.edit');
    Route::put('/lengkapi-profil', [StudentProfileController::class, 'update'])->name('students.profile.update');

    Route::get('/change-password', [AuthController::class, 'change'])
        ->name('password.change');

    Route::put('/change-password', [AuthController::class, 'updatePassword'])
        ->name('password.update');
    Route::get('/profile', [AuthController::class, 'profile'])
        ->name('profile');
    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('logout');
});
