<?php

namespace Database\Seeders;

use App\Models\Frontend\Promo\PriceProductSetting;
use App\Models\Frontend\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PriceProductSettingSeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::limit(5)->get();

        PriceProductSetting::create([
            'id' => (string) Str::uuid(),
            'code' => 'DISCOUNT_20',
            'title' => 'Diskon 20% Produk Unggulan',
            'description' => 'Diskon 20% untuk produk pilihan',
            'type' => 1,
            'scope' => 1,
            'discount_type' => 1,
            'discount_value' => 20,
            'is_active' => true,
            'is_featured' => true,
            'sort_order' => 1,
        ])->products()->attach(
            $products->mapWithKeys(fn($p) => [$p->id => ['discount_type' => 1, 'discount_value' => 20]])->toArray()
        );

        PriceProductSetting::create([
            'id' => (string) Str::uuid(),
            'code' => 'VOLUME_TIER',
            'title' => 'Diskon Volume',
            'description' => 'Diskon bertambah untuk pembelian lebih banyak',
            'type' => 2,
            'scope' => 1,
            'volume_tiers' => [
                ['min_qty' => 2, 'discount' => 5],
                ['min_qty' => 5, 'discount' => 10],
                ['min_qty' => 10, 'discount' => 15],
            ],
            'is_active' => true,
            'is_featured' => false,
            'sort_order' => 2,
        ])->products()->attach(
            $products->
                pluck('id')->
                mapWithKeys(fn($id) => [$id => ['discount_type' => 2, 'discount_value' => 0]])->
                toArray()
        );
    }
}
