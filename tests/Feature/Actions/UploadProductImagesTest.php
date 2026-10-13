<?php

namespace Tests\Feature\Actions;

use App\Actions\UploadProductImages;
use App\Models\ProductImage;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class UploadProductImagesTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_database_failure_removes_new_files_and_preserves_existing_images(): void
    {
        Storage::fake(ProductImage::DISK);
        $existing = ProductImage::factory()->create();
        Storage::disk(ProductImage::DISK)->put($existing->image_path, 'existing');
        $created = 0;
        ProductImage::creating(function () use (&$created): void {
            if (++$created === 2) {
                throw new RuntimeException('Simulated database failure');
            }
        });
        $file = new UploadedFile(base_path('tests/Fixtures/product.png'), 'product.png', null, null, true);

        try {
            app(UploadProductImages::class)->handle($existing->product, [$file, $file]);
            $this->fail('The failed upload should throw.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated database failure', $exception->getMessage());
        }

        $this->assertDatabaseCount('product_images', 1);
        $this->assertModelExists($existing);
        Storage::disk(ProductImage::DISK)->assertExists($existing->image_path);
        $this->assertSame([$existing->image_path], Storage::disk(ProductImage::DISK)->allFiles());
    }
}
