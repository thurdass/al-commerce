<?php

namespace App\Http\Controllers;

use App\Actions\SearchPublicCatalog;
use App\Http\Requests\PublicSearchRequest;
use App\Models\Store;
use Illuminate\View\View;

class PublicStoreController extends Controller
{
    public function index(PublicSearchRequest $request, SearchPublicCatalog $search): View
    {
        return view('welcome', [
            'stores' => $search->stores($request->filters()),
            'filters' => $request->filters(),
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
