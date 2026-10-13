<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\StoreController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

Route::get('/', function (Request $request): View {
    return view('welcome', ['store' => $request->user()?->store]);
})->name('home');

Route::middleware('guest')->group(function (): void {
    Route::get('/cadastro', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/cadastro', [RegisteredUserController::class, 'store'])->name('register.store');
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')->name('logout');

Route::middleware('auth')->group(function (): void {
    Route::get('/minha-loja/criar', [StoreController::class, 'create'])->name('stores.create');
    Route::post('/minha-loja', [StoreController::class, 'store'])->name('stores.store');
    Route::get('/minha-loja/{store}/editar', [StoreController::class, 'edit'])->whereNumber('store')->name('stores.edit');
    Route::match(['put', 'patch'], '/minha-loja/{store}', [StoreController::class, 'update'])->whereNumber('store')->name('stores.update');
});
