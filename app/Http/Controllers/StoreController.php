<?php

namespace App\Http\Controllers;

use App\Actions\SaveStore;
use App\Http\Requests\CreateStoreRequest;
use App\Http\Requests\UpdateStoreRequest;
use App\Models\Store;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class StoreController extends Controller
{
    public function create(): View
    {
        Gate::authorize('create', Store::class);

        return view('stores.create');
    }

    public function store(CreateStoreRequest $request, SaveStore $saveStore): RedirectResponse
    {
        $saveStore->handle($request->user()->store()->make(), $request->validated());

        return to_route('home')->with('status', 'Loja criada com sucesso.');
    }

    public function edit(Store $store): View
    {
        Gate::authorize('update', $store);

        return view('stores.edit', ['store' => $store]);
    }

    public function update(UpdateStoreRequest $request, Store $store, SaveStore $saveStore): RedirectResponse
    {
        $saveStore->handle($store, $request->validated());

        return to_route('stores.edit', $store)->with('status', 'Loja atualizada com sucesso.');
    }
}
