<?php

namespace App\Actions;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use League\Flysystem\FilesystemException;
use Throwable;

class DeleteProduct
{
    public function handle(Product $product): void
    {
        $disk = Storage::disk(ProductImage::DISK);
        $removedFiles = [];

        try {
            DB::transaction(function () use ($product, $disk, &$removedFiles): void {
                $product = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();
                $backups = [];

                foreach ($product->images()->get() as $image) {
                    if ($disk->exists($image->image_path)) {
                        $backups[$image->image_path] = $disk->get($image->image_path);
                    }
                }

                $product->delete();

                foreach ($backups as $path => $contents) {
                    $removedFiles[$path] = $contents;
                    $disk->delete($path);
                }
            });
        } catch (Throwable $exception) {
            foreach ($removedFiles as $path => $contents) {
                try {
                    $disk->put($path, $contents);
                } catch (FilesystemException $restoreException) {
                    report($restoreException);
                }
            }

            if ($exception instanceof FilesystemException) {
                report($exception);

                throw ValidationException::withMessages(['confirmed' => 'Não foi possível excluir as imagens do produto. Tente novamente.']);
            }

            throw $exception;
        }
    }
}
