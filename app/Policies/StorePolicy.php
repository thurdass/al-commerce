<?php

namespace App\Policies;

use App\Models\Store;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class StorePolicy
{
    public function create(User $user): Response
    {
        return $user->store()->exists()
            ? Response::deny('Você já possui uma loja.')
            : Response::allow();
    }

    public function update(User $user, Store $store): Response
    {
        return $user->id === $store->user_id
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
