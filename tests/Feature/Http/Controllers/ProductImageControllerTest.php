<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Store;
use App\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\UnableToWriteFile;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProductImageControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_owner_uploads_multiple_images_with_safe_names_and_first_image_as_cover(): void
    {
        Storage::fake(ProductImage::DISK);
        $product = Product::factory()->create();
        $other = Product::factory()->create();

        $this->actingAs($product->store->user)->post(route('products.images.store', $product), [
            'images' => [$this->image('png'), $this->image('jpg'), $this->image('webp')],
            'product_id' => $other->id, 'image_path' => '../../forged.png', 'position' => 0,
        ])->assertRedirectToRoute('products.edit', $product)->assertSessionHasNoErrors();

        $images = $product->images()->get();
        $this->assertCount(3, $images);
        $this->assertSame([1, 2, 3], $images->pluck('position')->all());
        $this->assertSame($images->first()->id, $product->coverImage->id);
        $this->assertSame(0, $other->images()->count());
        Storage::disk(ProductImage::DISK)->assertExists($images->pluck('image_path')->all());
        foreach ($images as $image) {
            $this->assertMatchesRegularExpression('/^'.$product->id.'\/[a-zA-Z0-9]{40}\.(png|jpg|webp)$/', $image->image_path);
            $this->assertArrayNotHasKey('image_path', $image->toArray());
        }
    }

    public function test_edit_and_list_show_authenticated_image_urls_without_storage_paths(): void
    {
        $image = ProductImage::factory()->create();
        $product = $image->product;

        $this->actingAs($product->store->user)->get(route('products.edit', $product))->assertOk()
            ->assertSee('enctype="multipart/form-data"', false)->assertSee('name="images[]"', false)
            ->assertSee(route('products.images.show', [$product, $image]))->assertSee('Capa')
            ->assertDontSee($image->image_path)->assertDontSee(storage_path());
        $this->get(route('products.index'))->assertOk()
            ->assertSee(route('products.images.show', [$product, $image]))->assertDontSee($image->image_path);
    }

    public function test_owner_can_read_image_bytes_with_private_cache_headers(): void
    {
        Storage::fake(ProductImage::DISK);
        $image = ProductImage::factory()->create();
        $content = file_get_contents(base_path('tests/Fixtures/product.png'));
        Storage::disk(ProductImage::DISK)->put($image->image_path, $content);

        $response = $this->actingAs($image->product->store->user)
            ->get(route('products.images.show', [$image->product, $image]));

        $response->assertOk()->assertHeader('Content-Type', 'image/png')
            ->assertHeader('X-Content-Type-Options', 'nosniff')->assertStreamedContent($content);
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
        $this->assertStringNotContainsString($image->image_path, $response->headers->get('Content-Disposition'));
        Storage::disk(ProductImage::DISK)->assertExists($image->image_path);
    }

    public function test_guest_cannot_upload_or_view_images(): void
    {
        Storage::fake(ProductImage::DISK);
        $image = ProductImage::factory()->create();

        $this->post(route('products.images.store', $image->product), ['images' => [$this->image()]])
            ->assertRedirectToRoute('login');
        $this->get(route('products.images.show', [$image->product, $image]))->assertRedirectToRoute('login');

        $this->assertDatabaseCount('product_images', 1);
        Storage::disk(ProductImage::DISK)->assertDirectoryEmpty('/');
    }

    public function test_other_merchant_cannot_upload_or_view_images(): void
    {
        Storage::fake(ProductImage::DISK);
        $image = ProductImage::factory()->create();
        $other = Store::factory()->create();

        $this->actingAs($other->user)->post(route('products.images.store', $image->product), ['images' => [$this->image()]])
            ->assertNotFound();
        $this->get(route('products.images.show', [$image->product, $image]))->assertNotFound();

        $this->assertDatabaseCount('product_images', 1);
        Storage::disk(ProductImage::DISK)->assertDirectoryEmpty('/');
    }

    public function test_user_without_store_cannot_upload_images(): void
    {
        Storage::fake(ProductImage::DISK);
        $product = Product::factory()->create();

        $this->actingAs(User::factory()->create())->post(route('products.images.store', $product), ['images' => [$this->image()]])
            ->assertNotFound();

        $this->assertDatabaseCount('product_images', 0);
        Storage::disk(ProductImage::DISK)->assertDirectoryEmpty('/');
    }

    public function test_image_must_belong_to_product_in_url_even_for_the_same_owner(): void
    {
        $product = Product::factory()->create();
        $another = Product::factory()->for($product->store)->create();
        $image = ProductImage::factory()->for($another)->create();

        $this->actingAs($product->store->user)->get(route('products.images.show', [$product, $image]))->assertNotFound();
    }

    public function test_missing_image_file_returns_not_found(): void
    {
        Storage::fake(ProductImage::DISK);
        $image = ProductImage::factory()->create();

        $this->actingAs($image->product->store->user)->get(route('products.images.show', [$image->product, $image]))
            ->assertNotFound();

        Storage::disk(ProductImage::DISK)->assertMissing($image->image_path);
    }

    public function test_additional_uploads_preserve_cover_and_allow_exactly_six_images(): void
    {
        Storage::fake(ProductImage::DISK);
        $product = Product::factory()->create();
        $images = ProductImage::factory()->count(3)->for($product)->sequence(['position' => 1], ['position' => 2], ['position' => 3])->create();

        $this->actingAs($product->store->user)->post(route('products.images.store', $product), [
            'images' => [$this->image(), $this->image(), $this->image()],
        ])->assertRedirectToRoute('products.edit', $product)->assertSessionHasNoErrors();

        $this->assertSame(6, $product->images()->count());
        $this->assertSame($images->first()->id, $product->coverImage->id);
        Storage::disk(ProductImage::DISK)->assertCount((string) $product->id, 3);
        $this->get(route('products.edit', $product))->assertOk()->assertDontSee('name="images[]"', false);
    }

    public function test_total_limit_rejects_entire_batch_without_saving_files(): void
    {
        Storage::fake(ProductImage::DISK);
        $product = Product::factory()->create();
        ProductImage::factory()->count(5)->for($product)->create();

        $this->actingAs($product->store->user)->post(route('products.images.store', $product), [
            'images' => [$this->image(), $this->image()],
        ])->assertSessionHasErrors(['images' => 'Cada produto pode ter no máximo 6 imagens.']);

        $this->assertSame(5, $product->images()->count());
        Storage::disk(ProductImage::DISK)->assertDirectoryEmpty('/');
    }

    #[DataProvider('invalidUploads')]
    public function test_rejects_invalid_uploads_without_persisting_partial_batch(string $case, string $field, string $message): void
    {
        Storage::fake(ProductImage::DISK);
        $product = Product::factory()->create();
        $files = match ($case) {
            'missing' => [],
            'string' => 'not files',
            'too many' => [$this->image(), $this->image(), $this->image(), $this->image()],
            'too large' => [$this->image(), UploadedFile::fake()->createWithContent('large.png', file_get_contents(base_path('tests/Fixtures/product.png')))->size(2049)],
            'forged jpeg' => [$this->image(), new UploadedFile($this->textFile(), 'photo.jpg', 'image/jpeg', null, true)],
            'svg' => [$this->image(), UploadedFile::fake()->createWithContent('vector.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>')],
            'gif' => [$this->image(), $this->image('gif')],
        };

        $this->actingAs($product->store->user)->post(route('products.images.store', $product), ['images' => $files])
            ->assertSessionHasErrors([$field => $message]);

        $this->assertDatabaseCount('product_images', 0);
        Storage::disk(ProductImage::DISK)->assertDirectoryEmpty('/');
    }

    public static function invalidUploads(): array
    {
        return [
            'missing' => ['missing', 'images', 'Selecione ao menos uma imagem.'],
            'string' => ['string', 'images', 'Envie as imagens em uma lista de arquivos.'],
            'too many' => ['too many', 'images', 'Envie no máximo 3 imagens por vez.'],
            'too large' => ['too large', 'images.1', 'Cada imagem deve ter no máximo 2 MB.'],
            'forged jpeg' => ['forged jpeg', 'images.1', 'Envie uma imagem JPEG, PNG ou WebP válida.'],
            'svg' => ['svg', 'images.1', 'Envie uma imagem JPEG, PNG ou WebP válida.'],
            'gif' => ['gif', 'images.1', 'Envie uma imagem JPEG, PNG ou WebP válida.'],
        ];
    }

    public function test_accepts_image_at_size_limit_and_ignores_original_filename(): void
    {
        Storage::fake(ProductImage::DISK);
        $product = Product::factory()->create();
        $file = UploadedFile::fake()->createWithContent('foto original.png', file_get_contents(base_path('tests/Fixtures/product.png')))->size(2048);

        $this->actingAs($product->store->user)->post(route('products.images.store', $product), ['images' => [$file]])
            ->assertRedirectToRoute('products.edit', $product)->assertSessionHasNoErrors();

        $image = $product->images()->sole();
        $this->assertStringEndsWith('.png', $image->image_path);
        $this->assertStringNotContainsString('foto original', $image->image_path);
        Storage::disk(ProductImage::DISK)->assertExists($image->image_path);
    }

    public function test_storage_failure_returns_safe_error_without_creating_records(): void
    {
        $disk = Storage::fake(ProductImage::DISK);
        $product = Product::factory()->create();
        $failingDisk = Mockery::mock(FilesystemAdapter::class);
        $failingDisk->shouldReceive('putFileAs')->once()->andThrow(UnableToWriteFile::atLocation('/private/internal/path'));
        Storage::set(ProductImage::DISK, $failingDisk);
        Exceptions::fake();

        $this->actingAs($product->store->user)->post(route('products.images.store', $product), ['images' => [$this->image()]])
            ->assertSessionHasErrors(['images' => 'Não foi possível salvar as imagens. Tente novamente.']);

        $this->assertDatabaseCount('product_images', 0);
        $disk->assertDirectoryEmpty('/');
        Exceptions::assertReported(UnableToWriteFile::class);
    }

    private function image(string $extension = 'png'): UploadedFile
    {
        return new UploadedFile(base_path('tests/Fixtures/product.'.$extension), 'product.'.$extension, null, null, true);
    }

    private function textFile(): string
    {
        return base_path('tests/Fixtures/not-image.txt');
    }
}
