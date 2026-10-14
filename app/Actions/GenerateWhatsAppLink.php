<?php

namespace App\Actions;

use App\Models\Product;
use App\Models\Store;

class GenerateWhatsAppLink
{
    public function handle(Store $store, ?Product $product = null): ?string
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

        if ($product !== null) {
            $url = route('public.products.show', ['store' => $store->slug, 'product' => $product->slug]);
            $message = "Olá! Vi o produto {$product->name} na loja {$store->name} pelo AL Commerce e gostaria de saber se ainda está disponível.\n{$url}";
        }

        return 'https://wa.me/'.$phone.'?text='.rawurlencode($message);
    }
}
