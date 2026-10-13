<?php

namespace App\Http\Controllers;

use App\Actions\CreateProduct;
use App\Http\Requests\CreateProductRequest;
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
}
