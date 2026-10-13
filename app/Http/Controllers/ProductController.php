<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ProductController extends Controller
{
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
