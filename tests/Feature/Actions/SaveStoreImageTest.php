<?php

namespace Tests\Feature\Actions;

use App\Actions\SaveStoreImage;
use App\Models\Store;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToWriteFile;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class SaveStoreImageTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_database_failure_preserves_previous_image_and_removes_new_file(): void
    {
        $disk = Storage::fake(Store::IMAGE_DISK);
        $store = Store::factory()->create(['logo' => 'previous.png']);
        $disk->put($store->logo, 'previous bytes');
        Store::updating(function (): void {
            throw new RuntimeException('Simulated database failure');
        });

        try {
            app(SaveStoreImage::class)->handle($store, $this->image());
            $this->fail('The upload should fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated database failure', $exception->getMessage());
        }

        $this->assertSame('previous.png', $store->fresh()->logo);
        $this->assertSame(['previous.png'], $disk->allFiles());
        $this->assertSame('previous bytes', $disk->get('previous.png'));
    }

    public function test_replacement_failure_restores_previous_file_and_removes_new_upload(): void
    {
        $disk = Storage::fake(Store::IMAGE_DISK);
        $store = Store::factory()->create(['logo' => 'previous.png']);
        $disk->put($store->logo, 'previous bytes');
        $failingDisk = Mockery::mock($disk)->makePartial();
        $failingDisk->shouldReceive('delete')->with('previous.png')->once()->andReturnUsing(function (string $path) use ($disk): void {
            $disk->delete($path);
            throw UnableToDeleteFile::atLocation('/private/path');
        });
        Storage::set(Store::IMAGE_DISK, $failingDisk);
        Exceptions::fake();

        $this->actingAs($store->user)->post(route('stores.image.store', $store), ['image' => $this->image()])
            ->assertSessionHasErrors(['image' => 'Não foi possível atualizar a imagem da loja. Tente novamente.']);

        $this->assertSame('previous.png', $store->fresh()->logo);
        $this->assertSame(['previous.png'], $disk->allFiles());
        $this->assertSame('previous bytes', $disk->get('previous.png'));
        Exceptions::assertReported(UnableToDeleteFile::class);
    }

    public function test_write_failure_preserves_previous_image_and_returns_safe_error(): void
    {
        $disk = Storage::fake(Store::IMAGE_DISK);
        $store = Store::factory()->create(['logo' => 'previous.png']);
        $disk->put($store->logo, 'previous bytes');
        $failingDisk = Mockery::mock($disk)->makePartial();
        $failingDisk->shouldReceive('putFileAs')->once()->andThrow(UnableToWriteFile::atLocation('/private/path'));
        Storage::set(Store::IMAGE_DISK, $failingDisk);
        Exceptions::fake();

        $this->actingAs($store->user)->post(route('stores.image.store', $store), ['image' => $this->image()])
            ->assertSessionHasErrors(['image' => 'Não foi possível atualizar a imagem da loja. Tente novamente.']);

        $this->assertSame('previous.png', $store->fresh()->logo);
        $this->assertSame(['previous.png'], $disk->allFiles());
        Exceptions::assertReported(UnableToWriteFile::class);
    }

    public function test_removal_failure_keeps_image_association_and_bytes(): void
    {
        $disk = Storage::fake(Store::IMAGE_DISK);
        $store = Store::factory()->create(['logo' => 'previous.png']);
        $disk->put($store->logo, 'previous bytes');
        $failingDisk = Mockery::mock($disk)->makePartial();
        $failingDisk->shouldReceive('delete')->once()->andThrow(UnableToDeleteFile::atLocation('/private/path'));
        Storage::set(Store::IMAGE_DISK, $failingDisk);
        Exceptions::fake();

        $this->actingAs($store->user)->delete(route('stores.image.destroy', $store), ['confirmed' => '1'])
            ->assertSessionHasErrors(['image' => 'Não foi possível atualizar a imagem da loja. Tente novamente.']);

        $this->assertSame('previous.png', $store->fresh()->logo);
        $this->assertSame('previous bytes', $disk->get('previous.png'));
        Exceptions::assertReported(UnableToDeleteFile::class);
    }

    private function image(): UploadedFile
    {
        return new UploadedFile(base_path('tests/Fixtures/product.png'), 'product.png', null, null, true);
    }
}
