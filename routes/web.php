<?php

use App\Http\Controllers\LayerController;
use App\Http\Controllers\LayupController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

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

    Route::get('/suppliers/{supplier}/export', [SupplierController::class, 'export'])->name('suppliers.export');
    Route::get('/suppliers/{supplier}/import/review', [SupplierController::class, 'reviewImport'])->name('suppliers.import.review');
    Route::post('/suppliers/{supplier}/import', [SupplierController::class, 'import'])->name('suppliers.import');
    Route::post('/suppliers/{supplier}/import/resolve', [SupplierController::class, 'resolveImport'])->name('suppliers.import.resolve');
    Route::resource('suppliers', SupplierController::class);

    Route::scopeBindings()->group(function (): void {
        Route::resource('suppliers.layups', LayupController::class)
            ->except(['index', 'show']);
        Route::resource('suppliers.layups.layers', LayerController::class)
            ->except(['index', 'show']);
    });
});

require __DIR__.'/auth.php';
