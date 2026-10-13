<?php

namespace App\Http\Controllers;

use App\Actions\CreateProduct;
use App\Http\Requests\CreateProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Requests\UpdateProductStatusRequest;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function create(): View
    {
        Gate::authorize('create', Product::class);

        return view('products.create');
    }

    public function store(CreateProductRequest $request, CreateProduct $createProduct): RedirectResponse
    {
        $createProduct->handle($request->user()->store()->firstOrFail(), $request->validated());

        return to_route('products.index')->with('status', 'Produto cadastrado com sucesso.');
    }

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Product::class);
        $store = $request->user()->store()->firstOrFail();

        return view('products.index', [
            'store' => $store,
            'products' => $store->products()->orderByDesc('id')->paginate(15),
        ]);
    }

    public function edit(Product $product): View
    {
        Gate::authorize('update', $product);

        return view('products.edit', ['product' => $product]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $product->update($request->validated());

        return to_route('products.edit', $product)->with('status', 'Produto atualizado com sucesso.');
    }

    public function updateStatus(UpdateProductStatusRequest $request, Product $product): RedirectResponse
    {
        $product->update($request->validated());

        return to_route('products.index')->with('status', $product->active ? 'Produto ativado.' : 'Produto desativado.');
    }
}
