<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StoreController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware('guest')->group(function (): void {
    Route::get('/cadastro', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/cadastro', [RegisteredUserController::class, 'store'])->name('register.store');
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')->name('logout');

Route::middleware('auth')->group(function (): void {
    Route::get('/minha-loja/produtos/criar', [ProductController::class, 'create'])->name('products.create');
    Route::post('/minha-loja/produtos', [ProductController::class, 'store'])->name('products.store');
    Route::get('/minha-loja/produtos', [ProductController::class, 'index'])->name('products.index');
    Route::get('/minha-loja/produtos/{product}/editar', [ProductController::class, 'edit'])->whereNumber('product')->name('products.edit');
    Route::match(['put', 'patch'], '/minha-loja/produtos/{product}', [ProductController::class, 'update'])->whereNumber('product')->name('products.update');
    Route::patch('/minha-loja/produtos/{product}/status', [ProductController::class, 'updateStatus'])->whereNumber('product')->name('products.status');
    Route::get('/minha-loja/produtos/{product}/excluir', [ProductController::class, 'confirmDelete'])->whereNumber('product')->name('products.delete');
    Route::delete('/minha-loja/produtos/{product}', [ProductController::class, 'destroy'])->whereNumber('product')->name('products.destroy');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/minha-loja/criar', [StoreController::class, 'create'])->name('stores.create');
    Route::post('/minha-loja', [StoreController::class, 'store'])->name('stores.store');
    Route::get('/minha-loja/{store}/editar', [StoreController::class, 'edit'])->whereNumber('store')->name('stores.edit');
    Route::match(['put', 'patch'], '/minha-loja/{store}', [StoreController::class, 'update'])->whereNumber('store')->name('stores.update');
});
