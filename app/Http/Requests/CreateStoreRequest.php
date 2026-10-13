<?php

namespace App\Http\Requests;

use App\Models\Store;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Gate;

class CreateStoreRequest extends StoreRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('create', Store::class);
    }
}
