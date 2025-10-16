<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SupplierSyncController;

Route::get('/', function () {
    return view('welcome');
});

// Products admin
Route::get('/products', [ProductController::class, 'index'])->name('products.index');

// Supplier sync UI
Route::get('/sync', [SupplierSyncController::class, 'form'])->name('products.sync.form');
Route::post('/sync/csv', [SupplierSyncController::class, 'importCsv'])->name('products.sync.csv');
Route::post('/sync/api', [SupplierSyncController::class, 'fetchApi'])->name('products.sync.api');
