<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SQLController1;
use App\Http\Controllers\SQLController2;

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

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::group(['middleware' => ['auth', 'mahasiswa']], function() {
    Route::get('/sql', [SQLController1::class, 'index'])->name('sql.index');
    Route::post('/sql/execute', [SQLController1::class, 'execute'])->name('execute.query');
    Route::get('/select-database/{database}', [SQLController1::class, 'selectDatabase']);
    Route::get('/select-table/{database}/{table}', [SQLController1::class, 'selectTable']);
    Route::get('/view-table/{table}', [SQLController1::class, 'viewTable']);
    Route::post('/create-database', [SQLController1::class, 'createDatabase'])->name('create-database');
    Route::get('/databases', [SQLController1::class, 'getDatabases'])->name('databases');
    Route::post('/get-history', [SQLController1::class, 'getHistory'])->name('get-history');


});


// Route::get('/', [SQLController2::class, 'index'])->name('home');
// Route::post('/execute-query', [SQLController2::class, 'execute'])->name('execute.query');
// Route::get('/select-database/{database}', [SQLController2::class, 'selectDatabase'])->name('select.database');
// Route::get('/select-table/{database}/{table}', [SQLController2::class, 'selectTable']);
// Route::get('/view-table/{table}', [SQLController2::class, 'viewTable'])->name('view.table');


require __DIR__.'/auth.php';
