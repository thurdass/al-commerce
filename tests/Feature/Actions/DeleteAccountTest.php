<?php

namespace Tests\Feature\Actions;

use App\Actions\DeleteAccount;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToWriteFile;
use Mockery;
use PHPUnit\Framework\Attributes\TestWith;
use RuntimeException;
use Tests\TestCase;

class DeleteAccountTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_account_deletion_removes_all_owned_records_and_files_and_preserves_other_merchants(): void
    {
        $backups = Storage::fake(DeleteAccount::BACKUP_DISK);
        $storeDisk = Storage::fake(Store::IMAGE_DISK);
        $productDisk = Storage::fake(ProductImage::DISK);
        $store = Store::factory()->create(['logo' => 'owner/logo.jpg', 'cover_image' => 'owner/cover.jpg', 'active' => false]);
        $category = Category::factory()->create();
        $products = Product::factory()->count(2)->for($store)->for($category)->sequence(['active' => true], ['active' => false])->create();
        foreach ($products as $product) {
            $images = ProductImage::factory()->count(2)->for($product)->create();
            foreach ($images as $image) {
                $productDisk->put($image->image_path, 'owner product');
            }
        }
        $otherStore = Store::factory()->create(['logo' => 'other/logo.jpg']);
        $otherImage = ProductImage::factory()->for(Product::factory()->for($otherStore))->create();
        $productDisk->put($otherImage->image_path, 'other product');
        $storeDisk->put($store->logo, 'owner logo');
        $storeDisk->put($store->cover_image, 'owner cover');
        $storeDisk->put($otherStore->logo, 'other logo');
        $owner = $store->user;

        $this->actingAs($owner)->delete(route('account.destroy'), ['password' => 'password', 'confirmation' => 'EXCLUIR'])
            ->assertRedirectToRoute('home')->assertSessionHasNoErrors();

        $this->assertModelMissing($owner);
        $this->assertModelMissing($store);
        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseCount('product_images', 1);
        $this->assertModelExists($category);
        $this->assertModelExists($otherStore->user);
        $this->assertModelExists($otherStore);
        $this->assertModelExists($otherImage->product);
        $this->assertModelExists($otherImage);
        $this->assertSame([$otherImage->image_path], $productDisk->allFiles());
        $this->assertSame([$otherStore->logo], $storeDisk->allFiles());
        $this->assertSame('other product', $productDisk->get($otherImage->image_path));
        $this->assertSame('other logo', $storeDisk->get($otherStore->logo));
        $backups->assertDirectoryEmpty('/');
    }

    #[TestWith([false])]
    #[TestWith([true])]
    public function test_partial_storage_failure_rolls_back_database_and_restores_both_disks(bool $throw): void
    {
        $backups = Storage::fake(DeleteAccount::BACKUP_DISK);
        $storeDisk = Storage::fake(Store::IMAGE_DISK);
        $disk = Storage::fake(ProductImage::DISK);
        $store = Store::factory()->create(['logo' => 'owner/logo.jpg']);
        $product = Product::factory()->for($store)->create();
        $images = ProductImage::factory()->count(2)->for($product)->sequence(['position' => 1], ['position' => 2])->create();
        $storeDisk->put($store->logo, 'original logo');
        foreach ($images as $image) {
            $disk->put($image->image_path, 'original image');
        }
        $failing = Mockery::mock($disk)->makePartial();
        $failing->shouldReceive('delete')->with($images[1]->image_path)->once()->andReturnUsing(function () use ($throw): bool {
            if ($throw) {
                throw UnableToDeleteFile::atLocation('private-image');
            }

            return false;
        });
        Storage::set(ProductImage::DISK, $failing);
        Exceptions::fake();
        $user = $store->user;

        $this->actingAs($user)->delete(route('account.destroy'), ['password' => 'password', 'confirmation' => 'EXCLUIR'])
            ->assertSessionHasErrors(['deletion' => 'A exclusão não foi concluída. Sua conta foi mantida. Tente novamente mais tarde.']);

        $this->assertAuthenticatedAs($user);
        $this->assertModelExists($user);
        $this->assertModelExists($store);
        $this->assertModelExists($product);
        $this->assertDatabaseCount('product_images', 2);
        $this->assertSame('original logo', $storeDisk->get($store->logo));
        foreach ($images as $image) {
            $this->assertSame('original image', $disk->get($image->image_path));
        }
        $backups->assertDirectoryEmpty('/');
        Exceptions::assertReported($throw ? UnableToDeleteFile::class : RuntimeException::class);
    }

    public function test_backup_read_failure_preserves_all_originals(): void
    {
        $backups = Storage::fake(DeleteAccount::BACKUP_DISK);
        $storeDisk = Storage::fake(Store::IMAGE_DISK);
        $disk = Storage::fake(ProductImage::DISK);
        $store = Store::factory()->create(['logo' => 'logo.jpg']);
        $image = ProductImage::factory()->for(Product::factory()->for($store))->create();
        $storeDisk->put($store->logo, 'logo');
        $disk->put($image->image_path, 'image');
        $failing = Mockery::mock($disk)->makePartial();
        $failing->shouldReceive('readStream')->with($image->image_path)->andThrow(UnableToReadFile::fromLocation('private-image'));
        Storage::set(ProductImage::DISK, $failing);
        Exceptions::fake();

        $this->actingAs($store->user)->delete(route('account.destroy'), ['password' => 'password', 'confirmation' => 'EXCLUIR'])
            ->assertSessionHasErrors('deletion');

        $this->assertModelExists($store->user);
        $this->assertModelExists($image);
        $this->assertSame('logo', $storeDisk->get($store->logo));
        $this->assertSame('image', $disk->get($image->image_path));
        $backups->assertDirectoryEmpty('/');
        Exceptions::assertReported(UnableToReadFile::class);
    }

    public function test_failed_database_deletion_restores_files(): void
    {
        $backups = Storage::fake(DeleteAccount::BACKUP_DISK);
        $disk = Storage::fake(Store::IMAGE_DISK);
        $store = Store::factory()->create(['logo' => 'logo.jpg']);
        $disk->put($store->logo, 'logo');
        User::deleting(function (): void {
            throw new RuntimeException('Database deletion failed.');
        });
        Exceptions::fake();

        $this->actingAs($store->user)->delete(route('account.destroy'), ['password' => 'password', 'confirmation' => 'EXCLUIR'])
            ->assertSessionHasErrors('deletion');

        $this->assertModelExists($store->user);
        $this->assertModelExists($store);
        $this->assertSame('logo', $disk->get($store->logo));
        $backups->assertDirectoryEmpty('/');
        Exceptions::assertReported(RuntimeException::class);
    }

    public function test_backup_write_failure_aborts_before_removing_any_original(): void
    {
        $backups = Storage::fake(DeleteAccount::BACKUP_DISK);
        $disk = Storage::fake(Store::IMAGE_DISK);
        $store = Store::factory()->create(['logo' => 'logo.jpg']);
        $disk->put($store->logo, 'original logo');
        $failing = Mockery::mock($backups)->makePartial();
        $failing->shouldReceive('put')->andReturn(false);
        Storage::set(DeleteAccount::BACKUP_DISK, $failing);
        Exceptions::fake();

        $this->actingAs($store->user)->delete(route('account.destroy'), ['password' => 'password', 'confirmation' => 'EXCLUIR'])
            ->assertSessionHasErrors('deletion');

        $this->assertModelExists($store->user);
        $this->assertModelExists($store);
        $this->assertSame('original logo', $disk->get($store->logo));
        $backups->assertDirectoryEmpty('/');
        Exceptions::assertReported(RuntimeException::class);
    }

    public function test_recovery_of_interrupted_request_restores_only_files_still_referenced_by_owner(): void
    {
        $backups = Storage::fake(DeleteAccount::BACKUP_DISK);
        $disk = Storage::fake(Store::IMAGE_DISK);
        $store = Store::factory()->create(['logo' => 'logo.jpg', 'cover_image' => 'cover.jpg']);
        $disk->put('cover.jpg', 'current cover');
        $manifest = [];
        foreach (['logo.jpg', 'cover.jpg', 'removed.jpg'] as $index => $path) {
            $backup = $store->user_id.'/files/'.$index;
            $backups->put($backup, 'previous '.$path);
            $manifest[] = ['disk' => Store::IMAGE_DISK, 'path' => $path, 'backup' => $backup];
        }
        $backups->put($store->user_id.'/manifest.json', json_encode($manifest, JSON_THROW_ON_ERROR));

        $this->artisan('accounts:recover-deletions')->assertSuccessful();

        $this->assertSame('previous logo.jpg', $disk->get('logo.jpg'));
        $this->assertSame('current cover', $disk->get('cover.jpg'));
        $disk->assertMissing('removed.jpg');
        $backups->assertDirectoryEmpty('/');
        $this->assertModelExists($store->user);
    }

    public function test_missing_image_file_does_not_prevent_account_deletion(): void
    {
        $backups = Storage::fake(DeleteAccount::BACKUP_DISK);
        $disk = Storage::fake(Store::IMAGE_DISK);
        $store = Store::factory()->create(['logo' => 'missing.jpg']);
        $user = $store->user;

        $this->actingAs($user)->delete(route('account.destroy'), ['password' => 'password', 'confirmation' => 'EXCLUIR'])
            ->assertRedirectToRoute('home');

        $this->assertModelMissing($user);
        $disk->assertDirectoryEmpty('/');
        $backups->assertDirectoryEmpty('/');
    }

    public function test_failed_restoration_keeps_durable_backup_and_command_retries_it(): void
    {
        $backups = Storage::fake(DeleteAccount::BACKUP_DISK);
        $disk = Storage::fake(Store::IMAGE_DISK);
        $store = Store::factory()->create(['logo' => 'logo.jpg']);
        $disk->put($store->logo, 'original logo');
        $failing = Mockery::mock($disk)->makePartial();
        $failing->shouldReceive('put')->andThrow(UnableToWriteFile::atLocation('private-image'));
        Storage::set(Store::IMAGE_DISK, $failing);
        User::deleting(function (): void {
            throw new RuntimeException('Database deletion failed.');
        });
        Exceptions::fake();
        $user = $store->user;

        $this->actingAs($user)->delete(route('account.destroy'), ['password' => 'password', 'confirmation' => 'EXCLUIR'])
            ->assertSessionHasErrors('deletion');

        $this->assertModelExists($user);
        $this->assertModelExists($store);
        $backups->assertExists($user->id.'/manifest.json');
        $this->assertSame('original logo', $backups->get($user->id.'/files/0'));
        Exceptions::assertReported(UnableToWriteFile::class);
        $this->artisan('accounts:recover-deletions')->assertFailed();
        Storage::set(Store::IMAGE_DISK, $disk);
        $this->artisan('accounts:recover-deletions')->assertSuccessful();
        $this->assertSame('original logo', $disk->get($store->logo));
        $backups->assertDirectoryEmpty('/');
        $this->artisan('accounts:recover-deletions')->assertSuccessful();
    }

    public function test_backup_cleanup_failure_after_commit_is_retried_without_restoring_deleted_account(): void
    {
        $backups = Storage::fake(DeleteAccount::BACKUP_DISK);
        $disk = Storage::fake(Store::IMAGE_DISK);
        $store = Store::factory()->create(['logo' => 'logo.jpg']);
        $disk->put($store->logo, 'logo');
        $user = $store->user;
        $failing = Mockery::mock($backups)->makePartial();
        $failing->shouldReceive('deleteDirectory')->with((string) $user->id)->andReturn(true, false);
        Storage::set(DeleteAccount::BACKUP_DISK, $failing);
        Exceptions::fake();

        $this->actingAs($user)->delete(route('account.destroy'), ['password' => 'password', 'confirmation' => 'EXCLUIR'])
            ->assertRedirectToRoute('home')->assertSessionHasNoErrors();

        $this->assertModelMissing($user);
        $this->assertGuest();
        $disk->assertMissing($store->logo);
        $backups->assertExists($user->id.'/manifest.json');
        Exceptions::assertReported(RuntimeException::class);
        Storage::set(DeleteAccount::BACKUP_DISK, $backups);
        $this->artisan('accounts:recover-deletions')->assertSuccessful();
        $backups->assertDirectoryEmpty('/');
        $disk->assertDirectoryEmpty('/');
    }
}
