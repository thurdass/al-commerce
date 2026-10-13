<?php

namespace App\Actions;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Facades\DB;

class SetProductCover
{
    public function handle(Product $product, ProductImage $image): void
    {
        DB::transaction(function () use ($product, $image): void {
            $product = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();
            $cover = $product->images()->whereKey($image->id)->firstOrFail();
            $others = $product->images()->whereKeyNot($cover->id)->get();
            $cover->update(['position' => 1]);

            foreach ($others as $index => $other) {
                $other->update(['position' => $index + 2]);
            }
        });
    }
}
