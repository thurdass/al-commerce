<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProductPolicy
{
    public function viewAny(User $user): Response
    {
        return $user->store()->exists()
            ? Response::allow()
            : Response::deny('Crie sua loja antes de gerenciar produtos.');
    }
}
