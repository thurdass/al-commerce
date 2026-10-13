<?php

namespace Tests\Feature\Actions;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\UnableToDeleteFile;
use Mockery;
use Tests\TestCase;

class DeleteProductTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_confirmed_product_deletion_removes_all_its_images_and_preserves_other_products(): void
    {
        Storage::fake(ProductImage::DISK);
        $product = Product::factory()->create();
        $images = ProductImage::factory()->count(2)->for($product)->create();
        $other = ProductImage::factory()->create();
        foreach ($images->concat([$other]) as $image) {
            Storage::disk(ProductImage::DISK)->put($image->image_path, 'bytes');
        }

        $this->actingAs($product->store->user)->delete(route('products.destroy', $product), ['confirmed' => '1'])
            ->assertRedirectToRoute('products.index')->assertSessionHasNoErrors();

        $this->assertModelMissing($product);
        $this->assertDatabaseCount('product_images', 1);
        $this->assertModelExists($other);
        Storage::disk(ProductImage::DISK)->assertMissing($images->pluck('image_path')->all());
        Storage::disk(ProductImage::DISK)->assertExists($other->image_path);
    }

    public function test_deletion_by_another_merchant_preserves_product_and_image_file(): void
    {
        Storage::fake(ProductImage::DISK);
        $image = ProductImage::factory()->create();
        $other = Product::factory()->create();
        Storage::disk(ProductImage::DISK)->put($image->image_path, 'bytes');

        $this->actingAs($other->store->user)->delete(route('products.destroy', $image->product), ['confirmed' => '1'])
            ->assertNotFound();

        $this->assertModelExists($image->product);
        $this->assertModelExists($image);
        Storage::disk(ProductImage::DISK)->assertExists($image->image_path);
    }

    public function test_partial_storage_failure_restores_files_and_rolls_back_product_deletion(): void
    {
        $disk = Storage::fake(ProductImage::DISK);
        $product = Product::factory()->create();
        $images = ProductImage::factory()->count(2)->for($product)->sequence(['position' => 1], ['position' => 2])->create();
        foreach ($images as $image) {
            $disk->put($image->image_path, 'original bytes');
        }
        $failingDisk = Mockery::mock($disk)->makePartial();
        $failingDisk->shouldReceive('delete')->with($images[0]->image_path)->once()->andReturnUsing(fn (string $path): bool => $disk->delete($path));
        $failingDisk->shouldReceive('delete')->with($images[1]->image_path)->once()->andThrow(UnableToDeleteFile::atLocation('/private/path'));
        Storage::set(ProductImage::DISK, $failingDisk);
        Exceptions::fake();

        $this->actingAs($product->store->user)->delete(route('products.destroy', $product), ['confirmed' => '1'])
            ->assertSessionHasErrors(['confirmed' => 'Não foi possível excluir as imagens do produto. Tente novamente.']);

        $this->assertModelExists($product);
        $this->assertDatabaseCount('product_images', 2);
        $disk->assertExists($images->pluck('image_path')->all());
        $this->assertSame('original bytes', $disk->get($images[0]->image_path));
        Exceptions::assertReported(UnableToDeleteFile::class);
    }
}
