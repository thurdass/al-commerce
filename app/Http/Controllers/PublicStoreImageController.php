<?php

namespace App\Http\Controllers;

use App\Models\Store;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PublicStoreImageController extends Controller
{
    public function __invoke(Store $store): StreamedResponse
    {
        abort_unless($store->active && $store->logo !== null, 404);
        $disk = Storage::disk(Store::IMAGE_DISK);
        abort_unless($disk->exists($store->logo), 404);

        return $disk->response($store->logo, 'loja-'.$store->id, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
