<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TaskCompletionController;
use App\Http\Controllers\TaskController;
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
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/list', [TaskController::class, 'index'])->name('list');
Route::post('/list', [TaskController::class, 'store'])->name('store');
Route::post('/list/edit', [TaskController::class, 'edit'])->name('edit');
Route::get('/list/show/{id}', [TaskController::class, 'show'])->name('show');

Route::get('/list/create_completions/{id}', [TaskCompletionController::class, 'completeTaskById'])->name('complete');
Route::post('/list/delete_completion/', [TaskCompletionController::class, 'deleteTaskCompletionTaskById'])->name('delete_completion');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
