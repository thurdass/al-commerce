<?php

namespace App\Http\Controllers;

use App\Models\Store;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class PublicStoreController extends Controller
{
    public function index(): View
    {
        return view('welcome', [
            'stores' => Store::query()->active()
                ->withCount(['products as active_products_count' => fn (Builder $query) => $query->active()])
                ->with('presentationProduct.coverImage')
                ->orderBy('name')->orderBy('id')->paginate(12),
        ]);
    }

    public function show(Store $store): View
    {
        abort_unless($store->active, 404);

        return view('stores.show', [
            'store' => $store->load('presentationProduct.coverImage'),
            'products' => $store->products()->active()->with(['category', 'coverImage'])->orderByDesc('id')->paginate(12),
        ]);
    }
}
