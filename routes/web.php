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
    Route::get('/minha-loja/produtos', [ProductController::class, 'index'])->name('products.index');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/minha-loja/criar', [StoreController::class, 'create'])->name('stores.create');
    Route::post('/minha-loja', [StoreController::class, 'store'])->name('stores.store');
    Route::get('/minha-loja/{store}/editar', [StoreController::class, 'edit'])->whereNumber('store')->name('stores.edit');
    Route::match(['put', 'patch'], '/minha-loja/{store}', [StoreController::class, 'update'])->whereNumber('store')->name('stores.update');
});
