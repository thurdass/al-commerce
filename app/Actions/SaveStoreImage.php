<?php

namespace App\Actions;

use App\Models\Store;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use League\Flysystem\FilesystemException;
use Throwable;

class SaveStoreImage
{
    public function handle(Store $store, ?UploadedFile $file): void
    {
        $disk = Storage::disk(Store::IMAGE_DISK);
        $newPath = null;
        $oldPath = null;
        $oldContents = null;
        $deletingOldFile = false;

        try {
            DB::transaction(function () use ($store, $file, $disk, &$newPath, &$oldPath, &$oldContents, &$deletingOldFile): void {
                $store = Store::query()->whereKey($store->id)->lockForUpdate()->firstOrFail();
                $oldPath = $store->logo;
                $oldContents = $oldPath !== null && $disk->exists($oldPath) ? $disk->get($oldPath) : null;
                $newPath = $file?->store((string) $store->id, Store::IMAGE_DISK);
                $store->logo = $newPath;
                $store->save();

                if ($oldContents !== null) {
                    $deletingOldFile = true;
                    $disk->delete($oldPath);
                }
            });
        } catch (Throwable $exception) {
            if ($newPath !== null) {
                try {
                    $disk->delete($newPath);
                } catch (FilesystemException $cleanupException) {
                    report($cleanupException);
                }
            }

            if ($deletingOldFile) {
                try {
                    $disk->put($oldPath, $oldContents);
                } catch (FilesystemException $restoreException) {
                    report($restoreException);
                }
            }

            if ($exception instanceof FilesystemException) {
                report($exception);

                throw ValidationException::withMessages(['image' => 'Não foi possível atualizar a imagem da loja. Tente novamente.']);
            }

            throw $exception;
        }
    }
}
