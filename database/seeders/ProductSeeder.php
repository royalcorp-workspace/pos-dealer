<?php

namespace Database\Seeders;

use App\Models\Frontend\Product;
use App\Models\Frontend\ProductVariant;
use App\Models\Frontend\Brand;
use App\Models\Frontend\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $brand = Brand::firstOrCreate(
            ['slug' => 'royal-brand'],
            ['id' => (string) Str::uuid(), 'name' => 'Royal Brand']
        );

        $category = Category::firstOrCreate(
            ['slug' => 'spring-bed'],
            ['id' => (string) Str::uuid(), 'name' => 'Spring Bed']
        );

        for ($i = 1; $i <= 10; $i++) {
            $product = Product::create([
                'id' => (string) Str::uuid(),
                'brand_id' => $brand->id,
                'category_id' => $category->id,
                'name' => 'Spring Bed King Size ' . $i,
                'slug' => 'spring-bed-king-size-' . $i,
                'description' => 'Deskripsi produk spring bed king size ' . $i,
                'short_description' => 'Spring bed nyaman untuk tidur yang nyenyak',
                // 'price' => rand(1000000, 5000000),
                // 'cost_price' => rand(800000, 4000000),
                // 'stock' => rand(10, 100),
                // 'sku' => 'SKU-' . $i,
                // 'images' => [
                //     '/storage/products/spring-bed-' . $i . '-1.jpg',
                //     '/storage/products/spring-bed-' . $i . '-2.jpg',
                // ],
                // 'is_active' => true,
                // 'is_featured' => $i <= 3,
            ]);

            ProductVariant::create([
                'id' => (string) Str::uuid(),
                'product_id' => $product->id,
                'variant_name' => 'King Size - Medium',
                'sku' => 'SKU-' . $i . '-M',
                'price' => $product->price + 500000,
                'stock_qty' => rand(5, 50),
                // 'attributes' => ['size' => 'King', 'firmness' => 'Medium'],
            ]);

            ProductVariant::create([
                'id' => (string) Str::uuid(),
                'product_id' => $product->id,
                'variant_name' => 'Queen Size - Soft',
                'sku' => 'SKU-' . $i . '-Q',
                'price' => $product->price - 200000,
                'stock_qty' => rand(5, 50),
                // 'attributes' => ['size' => 'Queen', 'firmness' => 'Soft'],
            ]);
        }
    }
}
