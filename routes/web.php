<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
require __DIR__.'/dashboard/home.php';
require __DIR__.'/dashboard/role.php';
require __DIR__.'/dashboard/user.php';
require __DIR__.'/dashboard/category.php';
require __DIR__.'/dashboard/subcategory.php';
require __DIR__.'/dashboard/vendor.php';
require __DIR__.'/dashboard/asset.php';
