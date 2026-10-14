<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Store;
use Illuminate\View\View;

class PublicProductController extends Controller
{
    public function show(Store $store, Product $product): View
    {
        abort_unless($store->active && $product->active, 404);

        return view('products.show', [
            'store' => $store,
            'product' => $product->load(['category', 'images']),
        ]);
    }
}
