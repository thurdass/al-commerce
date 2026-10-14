<?php

namespace App\Http\Controllers;

use App\Actions\SearchPublicCatalog;
use App\Http\Requests\PublicSearchRequest;
use App\Models\Category;
use App\Models\Store;
use App\ProductAvailabilityStatus;
use Illuminate\View\View;

class PublicStoreController extends Controller
{
    public function index(PublicSearchRequest $request, SearchPublicCatalog $search): View
    {
        $filters = $request->filters();
        $productSearch = $request->searchesProducts();

        return view('welcome', [
            'stores' => $productSearch ? null : $search->stores($filters),
            'products' => $productSearch ? $search->products($filters) : null,
            'filters' => $filters,
            'productSearch' => $productSearch,
            'withinStore' => false,
            'categories' => $productSearch ? Category::query()->orderBy('name')->get(['name', 'slug']) : collect(),
            'availabilityStatuses' => ProductAvailabilityStatus::cases(),
        ]);
    }

    public function show(PublicSearchRequest $request, Store $store, SearchPublicCatalog $search): View
    {
        abort_unless($store->active, 404);

        return view('stores.show', [
            'store' => $store->load('presentationProduct.coverImage'),
            'products' => $search->products($request->filters(), $store),
            'filters' => $request->filters(),
            'productSearch' => true,
            'withinStore' => true,
            'categories' => Category::query()->orderBy('name')->get(['name', 'slug']),
            'availabilityStatuses' => ProductAvailabilityStatus::cases(),
        ]);
    }
}
