<?php

namespace App\Actions;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use League\Flysystem\FilesystemException;
use Throwable;

class DeleteProductImage
{
    public function handle(Product $product, ProductImage $image): void
    {
        $disk = Storage::disk(ProductImage::DISK);
        $backup = null;
        $path = null;
        $deletingFile = false;

        try {
            DB::transaction(function () use ($product, $image, $disk, &$backup, &$path, &$deletingFile): void {
                $product = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();
                $image = $product->images()->whereKey($image->id)->firstOrFail();
                $path = $image->image_path;
                $backup = $disk->exists($path) ? $disk->get($path) : null;
                $image->delete();

                if ($backup !== null) {
                    $deletingFile = true;
                    $disk->delete($path);
                }
            });
        } catch (Throwable $exception) {
            if ($deletingFile) {
                try {
                    $disk->put($path, $backup);
                } catch (FilesystemException $restoreException) {
                    report($restoreException);
                }
            }

            if ($exception instanceof FilesystemException) {
                report($exception);

                throw ValidationException::withMessages(['images' => 'Não foi possível remover a imagem. Tente novamente.']);
            }

            throw $exception;
        }
    }
}
