<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Gate;

class CreateProductRequest extends ProductRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('create', Product::class);
    }
}
