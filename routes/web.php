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

    Route::get('/suppliers', [SupplierController::class, 'index'])->name('suppliers.index');

    Route::middleware('can:manage-suppliers')->group(function (): void {
        Route::get('/suppliers/create', [SupplierController::class, 'create'])->name('suppliers.create');
        Route::post('/suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
        Route::get('/suppliers/{supplier}/edit', [SupplierController::class, 'edit'])->name('suppliers.edit');
        Route::put('/suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
        Route::patch('/suppliers/{supplier}', [SupplierController::class, 'update']);
        Route::delete('/suppliers/{supplier}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');
        Route::get('/suppliers/{supplier}/import/review', [SupplierController::class, 'reviewImport'])->name('suppliers.import.review');
        Route::post('/suppliers/{supplier}/import', [SupplierController::class, 'import'])->name('suppliers.import');
        Route::post('/suppliers/{supplier}/import/resolve', [SupplierController::class, 'resolveImport'])->name('suppliers.import.resolve');

        Route::scopeBindings()->group(function (): void {
            Route::resource('suppliers.layups', LayupController::class)
                ->except(['index', 'show']);
            Route::resource('suppliers.layups.layers', LayerController::class)
                ->except(['index', 'show']);
        });
    });

    Route::get('/suppliers/{supplier}/export', [SupplierController::class, 'export'])->name('suppliers.export');
    Route::get('/suppliers/{supplier}', [SupplierController::class, 'show'])->name('suppliers.show');
});

require __DIR__.'/auth.php';
