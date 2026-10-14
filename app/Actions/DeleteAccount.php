<?php

namespace App\Actions;

use App\Models\ProductImage;
use App\Models\Store;
use App\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class DeleteAccount
{
    public const BACKUP_DISK = 'account_deletions';

    public function handle(User $user): void
    {
        $userId = $user->id;

        try {
            DB::transaction(function () use ($userId): void {
                $user = User::query()->whereKey($userId)->lockForUpdate()->firstOrFail();
                $files = $this->accountFiles($user);
                $this->restore($userId, $files);
                $this->backup($userId, $files);

                foreach ($files as $file) {
                    if (! Storage::disk($file['disk'])->delete($file['path'])) {
                        throw new RuntimeException('Account image deletion failed.');
                    }
                }

                DB::table(config('session.table', 'sessions'))->where('user_id', $userId)->delete();
                DB::table(config('auth.passwords.users.table'))->where('email', $user->email)->delete();

                if (! $user->delete()) {
                    throw new RuntimeException('Account deletion was cancelled.');
                }
            });
        } catch (Throwable $exception) {
            report($exception);

            try {
                $this->recover($userId);
            } catch (Throwable $recoveryException) {
                report($recoveryException);
            }

            throw ValidationException::withMessages([
                'deletion' => 'A exclusão não foi concluída. Sua conta foi mantida. Tente novamente mais tarde.',
            ]);
        }

        try {
            $this->recover($userId);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    /**
     * A surviving account means the transaction rolled back; otherwise only backups remain.
     * Recovery uses the same row locks as deletion, including during scheduled retries.
     */
    public function recover(int $userId): void
    {
        DB::transaction(function () use ($userId): void {
            $user = User::query()->whereKey($userId)->lockForUpdate()->first();
            $this->restore($userId, $user ? $this->accountFiles($user) : []);
        });
    }

    /** @return list<array{disk: string, path: string}> */
    private function accountFiles(User $user): array
    {
        $store = $user->store()->lockForUpdate()->first();

        if (! $store) {
            return [];
        }

        $files = [];

        foreach (array_unique(array_filter([$store->logo, $store->cover_image])) as $path) {
            $files[] = ['disk' => Store::IMAGE_DISK, 'path' => $path];
        }

        foreach ($store->products()->orderBy('id')->lockForUpdate()->get(['id']) as $product) {
            foreach ($product->images()->lockForUpdate()->get(['image_path']) as $image) {
                $files[] = ['disk' => ProductImage::DISK, 'path' => $image->image_path];
            }
        }

        return $files;
    }

    /** @param list<array{disk: string, path: string}> $files */
    private function backup(int $userId, array $files): void
    {
        if ($files === []) {
            return;
        }

        $backups = Storage::disk(self::BACKUP_DISK);
        $manifest = [];

        foreach ($files as $file) {
            $disk = Storage::disk($file['disk']);

            if ($disk->exists($file['path'])) {
                $backup = $userId.'/files/'.count($manifest);
                $this->copy($disk, $file['path'], $backups, $backup);
                $manifest[] = [...$file, 'backup' => $backup];
            }
        }

        if (! $backups->put($userId.'/manifest.tmp', json_encode($manifest, JSON_THROW_ON_ERROR))
            || ! $backups->move($userId.'/manifest.tmp', $userId.'/manifest.json')) {
            throw new RuntimeException('Account image backup manifest could not be saved.');
        }
    }

    /** @param list<array{disk: string, path: string}> $referencedFiles */
    private function restore(int $userId, array $referencedFiles): void
    {
        $backups = Storage::disk(self::BACKUP_DISK);
        $manifestPath = $userId.'/manifest.json';

        if ($backups->exists($manifestPath)) {
            /** @var list<array{disk: string, path: string, backup: string}> $manifest */
            $manifest = json_decode($backups->get($manifestPath), true, flags: JSON_THROW_ON_ERROR);

            foreach ($manifest as $file) {
                $disk = Storage::disk($file['disk']);
                $temporaryPath = $file['path'].'.account-restore-'.$userId;
                $referenced = in_array(['disk' => $file['disk'], 'path' => $file['path']], $referencedFiles, true);

                if ($referenced && ! $disk->exists($file['path'])) {
                    $this->copy($backups, $file['backup'], $disk, $temporaryPath);

                    if (! $disk->move($temporaryPath, $file['path'])) {
                        throw new RuntimeException('Account image could not be restored.');
                    }
                }

                if (! $disk->delete($temporaryPath)) {
                    throw new RuntimeException('Account image recovery file could not be cleaned up.');
                }
            }
        }

        if (! $backups->deleteDirectory((string) $userId)) {
            throw new RuntimeException('Account image backups could not be cleaned up.');
        }
    }

    private function copy(FilesystemAdapter $source, string $sourcePath, FilesystemAdapter $target, string $targetPath): void
    {
        $stream = $source->readStream($sourcePath);

        if (! is_resource($stream)) {
            throw new RuntimeException('Account image could not be read.');
        }

        try {
            if (! $target->put($targetPath, $stream)) {
                throw new RuntimeException('Account image could not be copied.');
            }
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }
}
