<?php

use Illuminate\Support\Facades\Route;

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
    return file_get_contents(public_path('index.html'));
});
use App\Http\Controllers\Admin\CniController;

// Groupe admin avec middleware auth + admin
Route::prefix('admin')->name('admin.')->group(function () {

    // ── CNI ──────────────────────────────────────
    Route::prefix('cni')->name('cni.')->group(function () {

        Route::get ('/',                    [CniController::class, 'index'])    ->name('index');
        Route::get ('/verifier',            [CniController::class, 'verifier']) ->name('verifier');
        Route::get ('/{agent}',             [CniController::class, 'show'])     ->name('show');
        Route::get ('/image/{agent}',       [CniController::class, 'image'])    ->name('image');
        Route::get ('/stats/json',          [CniController::class, 'stats'])    ->name('stats');

        Route::post('/analyser',            [CniController::class, 'analyser']) ->name('analyser');
        Route::post('/valider/{agent}',     [CniController::class, 'valider'])  ->name('valider');
        Route::post('/decision/{agent}',    [CniController::class, 'decision']) ->name('decision');
    });
});

Route::get('/login', function () {
    return redirect('/login.html');
})->name('login');

