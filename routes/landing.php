<?php

use App\Http\Controllers\Landing\AccountDeletionController;
use App\Http\Controllers\Landing\LandingController;
use App\Http\Controllers\Landing\LegalController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| HALAMAN PUBLIK (landing, legal, hapus akun)
|--------------------------------------------------------------------------
| Bahasa Indonesia di /, English di /en.
*/

// Landing page.
Route::get('/', LandingController::class)->middleware('track:landing')->name('landing');
Route::get('/en', LandingController::class)->defaults('locale', 'en')->middleware('track:landing')->name('landing.en');

// Kebijakan Privasi & Syarat Layanan (wajib untuk Play Store / App Store).
Route::get('/kebijakan-privasi', LegalController::class)->defaults('page', 'privacy')->name('legal.privacy');
Route::get('/syarat-layanan', LegalController::class)->defaults('page', 'terms')->name('legal.terms');
Route::get('/en/privacy-policy', LegalController::class)->defaults('page', 'privacy')->defaults('locale', 'en')->name('legal.privacy.en');
Route::get('/en/terms-of-service', LegalController::class)->defaults('page', 'terms')->defaults('locale', 'en')->name('legal.terms.en');

// Permintaan hapus akun & data (URL untuk Google Play "Data deletion").
Route::get('/hapus-akun', [AccountDeletionController::class, 'create'])->name('account-deletion.create');
Route::post('/hapus-akun', [AccountDeletionController::class, 'store'])->middleware('throttle:5,10')->name('account-deletion.store');
Route::get('/en/delete-account', [AccountDeletionController::class, 'create'])->defaults('locale', 'en')->name('account-deletion.create.en');
