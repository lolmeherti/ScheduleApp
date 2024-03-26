<?php

use App\Http\Controllers\{
    ProfileController,
    TaskCompletionController,
    TaskController
};
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('auth.login');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

//Routes below only available to those already authenticated
Route::middleware(['auth'])->group(function () {
    Route::resource('list', TaskController::class)->only([
        'index', 'store', 'show'
    ]);
    Route::post('/list/edit', [TaskController::class, 'edit'])->name('list.edit');

    Route::prefix('list')->group(function () {
        Route::get(
            'create_completions/{id}',
            [TaskCompletionController::class, 'completeTaskById'])->name('complete')
        ;
        Route::post(
            'delete_completion',
            [TaskCompletionController::class, 'deleteTaskCompletionTaskById'])->name('delete_completion')
        ;
        Route::post(
            'search',
            [TaskCompletionController::class, 'searchCompletionsByTitle'])->name('search')
        ;
    });

    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });
});

require __DIR__ . '/auth.php';
