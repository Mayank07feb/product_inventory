<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;

// Display all products
Route::get('/', [ProductController::class, 'index'])->name('products.index');

// Store a new product
Route::post('/products', [ProductController::class, 'store'])->name('products.store');

// Delete a product
Route::delete('/products/{index}', [ProductController::class, 'destroy'])->name('products.destroy');

// Show the edit form for a specific product
Route::get('/products/edit/{index}', [ProductController::class, 'edit'])->name('products.edit');

// Update a specific product
Route::put('/products/{index}', [ProductController::class, 'update'])->name('products.update');

// View JSON data for all products
Route::get('/products/json', [ProductController::class, 'showJson'])->name('products.json');
