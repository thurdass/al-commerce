<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Store;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PublicProductImageController extends Controller
{
    public function __invoke(Store $store, Product $product, ProductImage $image): StreamedResponse
    {
        abort_unless($store->active && $product->active, 404);
        $disk = Storage::disk(ProductImage::DISK);
        abort_unless($disk->exists($image->image_path), 404);

        return $disk->response($image->image_path, 'imagem-'.$image->id, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
