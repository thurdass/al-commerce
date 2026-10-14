<?php

use App\Http\Controllers\Auth\AccountController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductImageController;
use App\Http\Controllers\PublicProductController;
use App\Http\Controllers\PublicProductImageController;
use App\Http\Controllers\PublicStoreController;
use App\Http\Controllers\PublicStoreImageController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\StoreImageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicStoreController::class, 'index'])->name('home');
Route::get('/loja/{store:slug}', [PublicStoreController::class, 'show'])->name('public.stores.show');
Route::get('/loja/{store:slug}/imagem', PublicStoreImageController::class)->name('public.stores.image');
Route::scopeBindings()->group(function (): void {
    Route::get('/loja/{store:slug}/produto/{product:slug}', [PublicProductController::class, 'show'])->name('public.products.show');
    Route::get('/loja/{store:slug}/produto/{product:slug}/imagem/{image}', PublicProductImageController::class)
        ->whereNumber('image')->name('public.products.images.show');
});

Route::middleware('guest')->group(function (): void {
    Route::get('/cadastro', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/cadastro', [RegisteredUserController::class, 'store'])->name('register.store');
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')->name('logout');

Route::middleware('auth')->group(function (): void {
    Route::get('/email/verificar', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verificar/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1,verification-link'])->name('verification.verify');
    Route::post('/email/reenviar-verificacao', EmailVerificationNotificationController::class)
        ->middleware('throttle:verification-email')->name('verification.send');
    Route::get('/minha-conta/excluir', [AccountController::class, 'confirmDelete'])->name('account.delete');
    Route::delete('/minha-conta', [AccountController::class, 'destroy'])->middleware('throttle:6,1')->name('account.destroy');
});

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('/minha-loja/produtos/criar', [ProductController::class, 'create'])->name('products.create');
    Route::post('/minha-loja/produtos', [ProductController::class, 'store'])->name('products.store');
    Route::get('/minha-loja/produtos', [ProductController::class, 'index'])->name('products.index');
    Route::post('/minha-loja/produtos/{product}/imagens', [ProductImageController::class, 'store'])->whereNumber('product')->name('products.images.store');
    Route::get('/minha-loja/produtos/{product}/imagens/{image}', [ProductImageController::class, 'show'])->whereNumber(['product', 'image'])->scopeBindings()->name('products.images.show');
    Route::patch('/minha-loja/produtos/{product}/imagens/{image}/capa', [ProductImageController::class, 'updateCover'])->whereNumber(['product', 'image'])->scopeBindings()->name('products.images.cover');
    Route::delete('/minha-loja/produtos/{product}/imagens/{image}', [ProductImageController::class, 'destroy'])->whereNumber(['product', 'image'])->scopeBindings()->name('products.images.destroy');
    Route::get('/minha-loja/produtos/{product}/editar', [ProductController::class, 'edit'])->whereNumber('product')->name('products.edit');
    Route::match(['put', 'patch'], '/minha-loja/produtos/{product}', [ProductController::class, 'update'])->whereNumber('product')->name('products.update');
    Route::patch('/minha-loja/produtos/{product}/status', [ProductController::class, 'updateStatus'])->whereNumber('product')->name('products.status');
    Route::get('/minha-loja/produtos/{product}/excluir', [ProductController::class, 'confirmDelete'])->whereNumber('product')->name('products.delete');
    Route::delete('/minha-loja/produtos/{product}', [ProductController::class, 'destroy'])->whereNumber('product')->name('products.destroy');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/minha-loja/criar', [StoreController::class, 'create'])->name('stores.create');
    Route::post('/minha-loja', [StoreController::class, 'store'])->name('stores.store');
    Route::post('/minha-loja/{store}/imagem', [StoreImageController::class, 'store'])->whereNumber('store')->name('stores.image.store');
    Route::get('/minha-loja/{store}/imagem', [StoreImageController::class, 'show'])->whereNumber('store')->name('stores.image.show');
    Route::delete('/minha-loja/{store}/imagem', [StoreImageController::class, 'destroy'])->whereNumber('store')->name('stores.image.destroy');
    Route::get('/minha-loja/{store}/editar', [StoreController::class, 'edit'])->whereNumber('store')->name('stores.edit');
    Route::match(['put', 'patch'], '/minha-loja/{store}', [StoreController::class, 'update'])->whereNumber('store')->name('stores.update');
});
