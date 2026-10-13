<?php

namespace App\Http\Controllers;

use App\Actions\DeleteProductImage;
use App\Actions\SetProductCover;
use App\Actions\UploadProductImages;
use App\Http\Requests\DeleteProductImageRequest;
use App\Http\Requests\UploadProductImagesRequest;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductImageController extends Controller
{
    public function store(UploadProductImagesRequest $request, Product $product, UploadProductImages $upload): RedirectResponse
    {
        $upload->handle($product, $request->validated('images'));

        return to_route('products.edit', $product)->with('status', 'Imagens adicionadas com sucesso.');
    }

    public function show(Product $product, ProductImage $image): StreamedResponse
    {
        Gate::authorize('update', $product);
        $disk = Storage::disk(ProductImage::DISK);
        abort_unless($disk->exists($image->image_path), 404);

        return $disk->response($image->image_path, 'imagem-'.$image->id, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function updateCover(Product $product, ProductImage $image, SetProductCover $setCover): RedirectResponse
    {
        Gate::authorize('update', $product);
        $setCover->handle($product, $image);

        return to_route('products.edit', $product)->with('status', 'Capa atualizada com sucesso.');
    }

    public function destroy(DeleteProductImageRequest $request, Product $product, ProductImage $image, DeleteProductImage $deleteImage): RedirectResponse
    {
        $deleteImage->handle($product, $image);

        return to_route('products.edit', $product)->with('status', 'Imagem removida com sucesso.');
    }
}
