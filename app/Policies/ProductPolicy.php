<?php

namespace App\Policies;

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
}
