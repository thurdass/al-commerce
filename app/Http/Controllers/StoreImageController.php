<?php

namespace App\Http\Controllers;

use App\Actions\SaveStoreImage;
use App\Http\Requests\DeleteStoreImageRequest;
use App\Http\Requests\UploadStoreImageRequest;
use App\Models\Store;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StoreImageController extends Controller
{
    public function store(UploadStoreImageRequest $request, Store $store, SaveStoreImage $saveImage): RedirectResponse
    {
        $saveImage->handle($store, $request->validated('image'));

        return to_route('stores.edit', $store)->with('status', 'Imagem da loja atualizada com sucesso.');
    }

    public function show(Store $store): StreamedResponse
    {
        Gate::authorize('update', $store);
        $disk = Storage::disk(Store::IMAGE_DISK);
        abort_unless($store->logo !== null && $disk->exists($store->logo), 404);

        return $disk->response($store->logo, 'loja-'.$store->id, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function destroy(DeleteStoreImageRequest $request, Store $store, SaveStoreImage $saveImage): RedirectResponse
    {
        $saveImage->handle($store, null);

        return to_route('stores.edit', $store)->with('status', 'Imagem da loja removida com sucesso.');
    }
}
