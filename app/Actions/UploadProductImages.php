<?php

namespace App\Actions;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use League\Flysystem\FilesystemException;
use Throwable;

class UploadProductImages
{
    /** @param array<UploadedFile> $files */
    public function handle(Product $product, array $files): void
    {
        $paths = [];
        $disk = Storage::disk(ProductImage::DISK);

        try {
            DB::transaction(function () use ($product, $files, &$paths): void {
                $product = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();

                if ($product->images()->count() + count($files) > ProductImage::MAX_PER_PRODUCT) {
                    throw ValidationException::withMessages(['images' => 'Cada produto pode ter no máximo 6 imagens.']);
                }

                $position = (int) $product->images()->max('position');

                foreach ($files as $file) {
                    $path = $file->store((string) $product->id, ProductImage::DISK);
                    $paths[] = $path;
                    $product->images()->create(['image_path' => $path, 'position' => ++$position]);
                }
            });
        } catch (Throwable $exception) {
            if ($paths !== []) {
                try {
                    $disk->delete($paths);
                } catch (FilesystemException $cleanupException) {
                    report($cleanupException);
                }
            }

            if ($exception instanceof FilesystemException) {
                report($exception);

                throw ValidationException::withMessages(['images' => 'Não foi possível salvar as imagens. Tente novamente.']);
            }

            throw $exception;
        }
    }
}
