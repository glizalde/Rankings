<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\PosAuthController;
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

Route::get('/', function () {
    return view('welcome');
});
// LOGIN (SIN middleware)
Route::get('/posicionamiento/login', [PosAuthController::class, 'showLogin'])
    ->name('pos.login');

Route::post('/posicionamiento/login', [PosAuthController::class, 'login'])
    ->name('pos.login.post');

// DASHBOARD (CON middleware)

Route::get('/posicionamiento/dashboard', [PosAuthController::class, 'dashboard'])->name('pos.dashboard')->middleware('auth');



// LOGOUT
Route::post('/posicionamiento/logout', [PosAuthController::class, 'logout'])->name('pos.logout');



// REGISTRO
Route::get('/posicionamiento/registro', [PosAuthController::class, 'showRegister'])
    ->name('pos.register');

Route::post('/posicionamiento/registro', [PosAuthController::class, 'register'])
    ->name('pos.register.post');
