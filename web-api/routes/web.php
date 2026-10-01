<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminWebController;

Route::get('/', fn () => view('landing'))->name('landing');
Route::get('/login', [AdminWebController::class, 'webLoginForm'])->name('web.login');
Route::post('/login', [AdminWebController::class, 'webLogin'])->name('web.login.store');
Route::get('/register', [AdminWebController::class, 'registerForm'])->name('web.register');
Route::post('/register', [AdminWebController::class, 'register'])->name('web.register.store');
Route::post('/logout', [AdminWebController::class, 'webLogout'])->name('web.logout');
Route::get('/employer', [AdminWebController::class, 'employerDashboard'])->name('employer.dashboard');
Route::post('/employer/requests', [AdminWebController::class, 'storeWebRequest'])->name('employer.requests.store');

Route::get('/admin/login', [AdminWebController::class, 'loginForm'])->name('admin.login');
Route::post('/admin/login', [AdminWebController::class, 'login'])->name('admin.login.store');
Route::post('/admin/logout', [AdminWebController::class, 'logout'])->name('admin.logout');
Route::get('/admin', [AdminWebController::class, 'dashboard'])->name('admin.dashboard');
Route::get('/admin/requests/{verificationRequest}', [AdminWebController::class, 'showRequest'])->name('admin.requests.show');
Route::patch('/admin/requests/{verificationRequest}/decision', [AdminWebController::class, 'decide'])->name('admin.requests.decide');
Route::get('/admin/requests/{verificationRequest}/documents/{document}', [AdminWebController::class, 'downloadRequestDocument'])->name('admin.requests.document');
Route::get('/admin/diplomas/{diploma}', [AdminWebController::class, 'showDiploma'])->name('admin.diplomas.show');
Route::get('/admin/diplomas/{diploma}/documents/{document}', [AdminWebController::class, 'downloadDiplomaDocument'])->name('admin.diplomas.document');
Route::patch('/admin/diplomas/{diploma}', [AdminWebController::class, 'updateDiploma'])->name('admin.diplomas.update');
Route::get('/admin/diplomas/{diploma}/documents/{document}', [AdminWebController::class, 'downloadDiplomaDocument'])->name('admin.diplomas.document');
Route::patch('/admin/employers/{user}/decision', [AdminWebController::class, 'decideEmployer'])->name('admin.employers.decide');
Route::post('/admin/diplomas', [AdminWebController::class, 'storeDiploma'])->name('admin.diplomas.store');
Route::get('/verify/{token}', [AdminWebController::class, 'verify'])->name('public.verify');
