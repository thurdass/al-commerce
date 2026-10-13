<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProductPolicy
{
    public function create(User $user): Response
    {
        return $this->viewAny($user);
    }

    public function viewAny(User $user): Response
    {
        return $user->store()->exists()
            ? Response::allow()
            : Response::deny('Crie sua loja antes de gerenciar produtos.');
    }

    public function update(User $user, Product $product): Response
    {
        return $user->store()->whereKey($product->store_id)->exists()
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function delete(User $user, Product $product): Response
    {
        return $this->update($user, $product);
    }
}
