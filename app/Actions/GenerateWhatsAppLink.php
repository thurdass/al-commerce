<?php

namespace App\Actions;

use App\Models\Store;

class GenerateWhatsAppLink
{
    public function handle(Store $store): ?string
    {
        $formatted = trim($store->whatsapp ?? '');

        if (! preg_match('/^\+?[0-9 ().-]+$/D', $formatted)) {
            return null;
        }

        $phone = preg_replace('/[^0-9]/', '', $formatted);

        if (! preg_match('/^[1-9][0-9]{9,14}$/D', $phone)) {
            return null;
        }

        $message = "Olá! Vi a loja {$store->name} pelo AL Commerce e gostaria de mais informações.";

        return 'https://wa.me/'.$phone.'?text='.rawurlencode($message);
    }
}
