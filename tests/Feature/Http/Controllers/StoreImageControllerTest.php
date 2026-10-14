<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Store;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class StoreImageControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[TestWith(['png'])]
    #[TestWith(['jpg'])]
    #[TestWith(['webp'])]
    public function test_owner_uploads_a_single_image_without_changing_store_identity(string $extension): void
    {
        Storage::fake(Store::IMAGE_DISK);
        $store = Store::factory()->inactive()->create();
        $other = Store::factory()->create();
        $slug = $store->slug;

        $this->actingAs($store->user)->post(route('stores.image.store', $store), [
            'image' => $this->image($extension), 'user_id' => $other->user_id,
            'store_id' => $other->id, 'logo' => '../../forged.png', 'slug' => 'forged', 'active' => true,
        ])->assertRedirectToRoute('stores.edit', $store)->assertSessionHasNoErrors();

        $store->refresh();
        $this->assertSame($slug, $store->slug);
        $this->assertFalse($store->active);
        $this->assertNotSame($other->user_id, $store->user_id);
        $this->assertNull($other->fresh()->logo);
        $this->assertMatchesRegularExpression('/^'.$store->id.'\/[a-zA-Z0-9]{40}\.'.$extension.'$/', $store->logo);
        Storage::disk(Store::IMAGE_DISK)->assertExists($store->logo);
        $this->get(route('stores.edit', $store))->assertOk()->assertSee('enctype="multipart/form-data"', false)
            ->assertSee(route('stores.image.show', $store))->assertSee('Substituir imagem')->assertDontSee($store->logo);
        $response = $this->get(route('stores.image.show', $store))->assertOk()
            ->assertStreamedContent(file_get_contents(base_path('tests/Fixtures/product.'.$extension)));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringNotContainsString($store->logo, $response->headers->get('Content-Disposition'));
    }

    public function test_replacement_removes_previous_file_and_preserves_another_stores_image(): void
    {
        Storage::fake(Store::IMAGE_DISK);
        $store = Store::factory()->create(['logo' => 'previous.png']);
        $other = Store::factory()->create(['logo' => 'other.png']);
        Storage::disk(Store::IMAGE_DISK)->put($store->logo, 'previous bytes');
        Storage::disk(Store::IMAGE_DISK)->put($other->logo, 'other bytes');

        $this->actingAs($store->user)->post(route('stores.image.store', $store), ['image' => $this->image()])
            ->assertRedirectToRoute('stores.edit', $store)->assertSessionHasNoErrors();

        $this->assertNotSame('previous.png', $store->fresh()->logo);
        Storage::disk(Store::IMAGE_DISK)->assertMissing('previous.png');
        Storage::disk(Store::IMAGE_DISK)->assertExists([$store->fresh()->logo, $other->logo]);
        $this->assertCount(2, Storage::disk(Store::IMAGE_DISK)->allFiles());
    }

    public function test_owner_removes_image_with_confirmation_and_preserves_store(): void
    {
        Storage::fake(Store::IMAGE_DISK);
        $store = Store::factory()->create(['logo' => 'previous.png']);
        Storage::disk(Store::IMAGE_DISK)->put($store->logo, 'previous bytes');

        $this->actingAs($store->user)->delete(route('stores.image.destroy', $store), ['confirmed' => '1'])
            ->assertRedirectToRoute('stores.edit', $store)->assertSessionHasNoErrors();

        $this->assertModelExists($store);
        $this->assertNull($store->fresh()->logo);
        Storage::disk(Store::IMAGE_DISK)->assertMissing('previous.png');
        $this->get(route('stores.image.show', $store))->assertNotFound();
        $this->get(route('stores.edit', $store))->assertOk()->assertSee('Adicionar imagem');
    }

    #[TestWith([null])]
    #[TestWith(['0'])]
    public function test_removal_without_confirmation_preserves_image(?string $confirmed): void
    {
        Storage::fake(Store::IMAGE_DISK);
        $store = Store::factory()->create(['logo' => 'previous.png']);
        Storage::disk(Store::IMAGE_DISK)->put($store->logo, 'bytes');

        $this->actingAs($store->user)->delete(route('stores.image.destroy', $store), ['confirmed' => $confirmed])
            ->assertSessionHasErrors(['confirmed' => 'Confirme a remoção da imagem da loja.']);

        $this->assertSame('previous.png', $store->fresh()->logo);
        Storage::disk(Store::IMAGE_DISK)->assertExists('previous.png');
    }

    #[DataProvider('imageEndpoints')]
    public function test_guest_cannot_manage_or_read_private_store_image(string $method, string $route): void
    {
        Storage::fake(Store::IMAGE_DISK);
        $store = Store::factory()->create(['logo' => 'previous.png']);
        Storage::disk(Store::IMAGE_DISK)->put($store->logo, 'bytes');

        $this->$method(route($route, $store), ['confirmed' => '1'])->assertRedirectToRoute('login');

        $this->assertSame('previous.png', $store->fresh()->logo);
        Storage::disk(Store::IMAGE_DISK)->assertExists($store->logo);
    }

    #[DataProvider('imageEndpoints')]
    public function test_other_merchant_cannot_manage_or_read_private_store_image(string $method, string $route): void
    {
        Storage::fake(Store::IMAGE_DISK);
        $store = Store::factory()->create(['logo' => 'previous.png']);
        $other = Store::factory()->create();
        Storage::disk(Store::IMAGE_DISK)->put($store->logo, 'bytes');

        $this->actingAs($other->user)->$method(route($route, $store), ['confirmed' => '1'])->assertNotFound();

        $this->assertSame('previous.png', $store->fresh()->logo);
        Storage::disk(Store::IMAGE_DISK)->assertExists($store->logo);
        $this->assertSame(['previous.png'], Storage::disk(Store::IMAGE_DISK)->allFiles());
    }

    public static function imageEndpoints(): array
    {
        return [
            'upload' => ['post', 'stores.image.store'],
            'read' => ['get', 'stores.image.show'],
            'delete' => ['delete', 'stores.image.destroy'],
        ];
    }

    #[DataProvider('invalidImages')]
    public function test_invalid_upload_preserves_current_image(string $case, string $message): void
    {
        Storage::fake(Store::IMAGE_DISK);
        $store = Store::factory()->create(['logo' => 'previous.png']);
        Storage::disk(Store::IMAGE_DISK)->put($store->logo, 'bytes');
        $image = match ($case) {
            'missing' => null,
            'array' => [$this->image(), $this->image()],
            'large' => UploadedFile::fake()->createWithContent('large.png', file_get_contents(base_path('tests/Fixtures/product.png')))->size(2049),
            'gif' => $this->image('gif'),
            'svg' => UploadedFile::fake()->createWithContent('image.svg', '<svg xmlns="http://www.w3.org/2000/svg"/>'),
            'fake jpeg' => new UploadedFile(base_path('tests/Fixtures/not-image.txt'), 'fake.jpg', 'image/jpeg', null, true),
        };

        $this->actingAs($store->user)->post(route('stores.image.store', $store), ['image' => $image])
            ->assertSessionHasErrors(['image' => $message]);

        $this->assertSame('previous.png', $store->fresh()->logo);
        $this->assertSame(['previous.png'], Storage::disk(Store::IMAGE_DISK)->allFiles());
    }

    public static function invalidImages(): array
    {
        return [
            'missing' => ['missing', 'Selecione uma imagem para a loja.'],
            'array' => ['array', 'Envie um arquivo de imagem válido.'],
            'large' => ['large', 'A imagem deve ter no máximo 2 MB.'],
            'gif' => ['gif', 'Envie uma imagem JPEG, PNG ou WebP válida.'],
            'svg' => ['svg', 'Envie uma imagem JPEG, PNG ou WebP válida.'],
            'fake jpeg' => ['fake jpeg', 'Envie uma imagem JPEG, PNG ou WebP válida.'],
        ];
    }

    public function test_upload_accepts_exact_size_limit(): void
    {
        Storage::fake(Store::IMAGE_DISK);
        $store = Store::factory()->create();
        $file = UploadedFile::fake()->createWithContent('image.png', file_get_contents(base_path('tests/Fixtures/product.png')))->size(2048);

        $this->actingAs($store->user)->post(route('stores.image.store', $store), ['image' => $file])
            ->assertRedirectToRoute('stores.edit', $store)->assertSessionHasNoErrors();

        Storage::disk(Store::IMAGE_DISK)->assertExists($store->fresh()->logo);
    }

    public function test_removing_a_missing_file_or_already_empty_image_is_safe(): void
    {
        Storage::fake(Store::IMAGE_DISK);
        $store = Store::factory()->create(['logo' => 'missing.png']);

        $this->actingAs($store->user)->get(route('stores.image.show', $store))->assertNotFound();
        $this->delete(route('stores.image.destroy', $store), ['confirmed' => '1'])->assertRedirectToRoute('stores.edit', $store);
        $this->delete(route('stores.image.destroy', $store), ['confirmed' => '1'])->assertRedirectToRoute('stores.edit', $store);

        $this->assertNull($store->fresh()->logo);
        Storage::disk(Store::IMAGE_DISK)->assertDirectoryEmpty('/');
    }

    private function image(string $extension = 'png'): UploadedFile
    {
        return new UploadedFile(base_path('tests/Fixtures/product.'.$extension), 'product.'.$extension, null, null, true);
    }
}
