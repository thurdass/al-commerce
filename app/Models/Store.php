<?php

namespace App\Models;

use App\ProductAvailabilityStatus;
use Database\Factories\StoreFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'name',
    'slug',
    'description',
    'category',
    'whatsapp',
    'city',
    'state',
    'address',
    'logo',
    'cover_image',
    'opening_hours',
    'active',
])]
class Store extends Model
{
    /** @use HasFactory<StoreFactory> */
    use HasFactory;

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<Product, $this> */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /** @return HasOne<Product, $this> */
    public function presentationProduct(): HasOne
    {
        return $this->hasOne(Product::class)->ofMany(['id' => 'max'], function (Builder $query): void {
            $query->active()
                ->whereIn('availability_status', [ProductAvailabilityStatus::Available, ProductAvailabilityStatus::LowStock])
                ->whereHas('images');
        });
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
            'active' => 'boolean',
        ];
    }
}
