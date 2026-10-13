<?php

namespace App\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Gate;

class UpdateStoreRequest extends StoreRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('update', $this->route('store'));
    }
}
