<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\PosAuthController;
use App\Http\Controllers\PosCaptureController;
use App\Http\Controllers\PosReviewController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::prefix('posicionamiento')->name('pos.')->group(function () {

    // Rutas públicas (solo invitados)
    Route::middleware('guest')->group(function () {
        Route::get('/login', [PosAuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [PosAuthController::class, 'login'])->name('login.post');

        Route::get('/registro', [PosAuthController::class, 'showRegister'])->name('register');
        Route::post('/registro', [PosAuthController::class, 'register'])->name('register.post');
    });

    // Rutas protegidas (solo autenticados)
    Route::middleware('auth')->group(function () {
        Route::get('/dashboard', [PosAuthController::class, 'dashboard'])->name('dashboard');
        Route::post('/logout', [PosAuthController::class, 'logout'])->name('logout');
        Route::get('/capture/{submission}', [PosCaptureController::class, 'show'])->name('capture');

        Route::post('/capture/{submission}/value/{variable}', [PosCaptureController::class, 'saveValue'])->name('capture.value.save');
        Route::post('/capture/{submission}/value/{variable}/word', [PosCaptureController::class, 'uploadWord'])->name('capture.word.upload');
        Route::post('/capture/{submission}/value/{variable}/links', [PosCaptureController::class, 'addLink'])->name('capture.links.add');
        Route::delete('/capture/links/{link}', [PosCaptureController::class, 'deleteLink'])->name('capture.links.delete');

        Route::post('/capture/{submission}/submit', [PosCaptureController::class, 'submit'])->name('capture.submit');



        // Admin: revisión
        Route::post('/review/{submission}/approve', [PosReviewController::class, 'approve'])->name('review.approve');
        Route::post('/review/{submission}/reject', [PosReviewController::class, 'reject'])->name('review.reject');
        Route::post('/review/{submission}/reopen', [PosReviewController::class, 'reopen'])->name('review.reopen'); // opcional
    });
});







// LOGIN (SIN middleware)




// DASHBOARD (CON middleware)





// LOGOUT




// REGISTRO
