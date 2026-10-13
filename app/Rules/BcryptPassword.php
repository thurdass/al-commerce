<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class BcryptPassword implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        if (strlen($value) > 72) {
            $fail('A senha é muito longa. Use uma senha menor.');
        }

        if (str_contains($value, "\0")) {
            $fail('A senha contém caracteres inválidos.');
        }
    }
}
