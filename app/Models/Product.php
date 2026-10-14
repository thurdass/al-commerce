<?php

namespace App\Models;

use App\ProductAvailabilityStatus;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'category_id',
    'name',
    'slug',
    'description',
    'price',
    'stock_quantity',
    'availability_status',
    'active',
])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /** @return BelongsTo<Store, $this> */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return HasMany<ProductImage, $this> */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('position')->orderBy('id');
    }

    /** @return HasOne<ProductImage, $this> */
    public function coverImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->ofMany(['position' => 'min', 'id' => 'min']);
    }

    /** @param Builder<self> $query */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('active', true);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'stock_quantity' => 'integer',
            'availability_status' => ProductAvailabilityStatus::class,
            'active' => 'boolean',
        ];
    }
}
