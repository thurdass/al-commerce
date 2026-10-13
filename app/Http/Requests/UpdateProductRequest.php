<?php

namespace App\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Gate;

class UpdateProductRequest extends ProductRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('update', $this->route('product'));
    }
}
