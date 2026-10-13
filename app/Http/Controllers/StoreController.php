<?php

namespace App\Http\Controllers;

use App\Actions\SaveStore;
use App\Http\Requests\CreateStoreRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StoreController extends Controller
{
    public function create(Request $request): View
    {
        abort_if($request->user()->store()->exists(), 403, 'Você já possui uma loja.');

        return view('stores.create');
    }

    public function store(CreateStoreRequest $request, SaveStore $saveStore): RedirectResponse
    {
        $saveStore->handle($request->user()->store()->make(), $request->validated());

        return to_route('home')->with('status', 'Loja criada com sucesso.');
    }
}
