<?php

namespace Database\Seeders;

use App\Models\Frontend\ProductsCatalog\Product;
use App\Models\Frontend\ProductsCatalog\ProductVariant;
use App\Models\Frontend\ProductsCatalog\ProductCategory;
use App\Models\Frontend\ProductsCatalog\Brand;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class JasonSeeder extends Seeder
{
    public function run(): void
    {
        $jsonPath = storage_path('app/jason.json');
        $data = json_decode(file_get_contents($jsonPath), true);

        $brands = $this->seedBrands();
        $categories = $this->seedCategories();

        foreach ($data['POS_F4101']['rowset'] as $item) {
            $brandId = $brands[$item['segment1']]['id'] ?? $this->createDefaultBrand($brands);
            $categoryId = $this->determineCategory($item['segment1'], $categories);

            $existingProduct = Product::where('slug', $this->generateSlug($item['description']))->first();
            if ($existingProduct) {
                continue;
            }

            $product = Product::create([
                'id' => (string) Str::uuid(),
                'brand_id' => $brandId,
                'category_id' => $categoryId,
                'name' => $item['description'],
                'slug' => $this->generateSlug($item['description']),
                'thumbnail' => null,
                'alt_text' => null,
                'short_description' => $item['description_line2'] ?? null,
                'description' => $item['description'],
                'base_price' => 0,
                'best_seller' => false,
                'is_new' => true,
                'sort_order' => 0,
                'status' => true,
            ]);

            ProductVariant::create([
                'id' => (string) Str::uuid(),
                'product_id' => $product->id,
                'sku' => $item['2nd_item_number'],
                'variant_name' => $this->generateVariantName($item),
                'width' => (int) ($item['segment5'] ?? 0),
                'length' => (int) ($item['segment6'] ?? 0),
                'height' => 0,
                'weight' => 0,
                'price' => 0,
                'stock_qty' => 0,
                'min_order_qty' => 1,
                'sort_order' => 0,
                'status' => true,
            ]);
        }
    }

    private function seedBrands(): array
    {
        $brandMappings = [
            'DV' => 'dvla',
            'HB' => 'hbed',
            'KM' => 'kama',
            'LB' => 'lbol',
            'LP' => 'lpillow',
            'LR' => 'lrp',
            'LT' => 'ltop',
            'SA' => 'sabed',
            'SB' => 'sbe',
            'ER' => 'erpro',
            'EB' => 'ebbol',
            'EM' => 'emtrv',
        ];

        $brands = [];
        $defaultBrandId = null;
        foreach ($brandMappings as $code => $slug) {
            $brands[$code] = Brand::firstOrCreate(
                ['slug' => $slug],
                ['id' => (string) Str::uuid(), 'name' => $this->getBrandName($code), 'status' => true, 'deleted' => false]
            );
        }
        
        $defaultBrandId = Brand::firstOrCreate(
            ['slug' => 'default-brand'],
            ['id' => (string) Str::uuid(), 'name' => 'Default', 'status' => true, 'deleted' => false]
        )->id;

        return $brands;
    }

    private function createDefaultBrand(array &$brands): string
    {
        return $brands['default'] ??= Brand::firstOrCreate(
            ['slug' => 'default-brand'],
            ['id' => (string) Str::uuid(), 'name' => 'Default', 'status' => true, 'deleted' => false]
        )->id;
    }

    private function seedCategories(): array
    {
        $categories = [];

        $categories['spring-bed'] = ProductCategory::firstOrCreate(
            ['slug' => 'spring-bed'],
            ['id' => (string) Str::uuid(), 'name' => 'Spring Bed']
        );

        $categories['mattress-protector'] = ProductCategory::firstOrCreate(
            ['slug' => 'mattress-protector'],
            ['id' => (string) Str::uuid(), 'name' => 'Mattress Protector']
        );

        $categories['bolster'] = ProductCategory::firstOrCreate(
            ['slug' => 'bolster'],
            ['id' => (string) Str::uuid(), 'name' => 'Bolster']
        );

        $categories['pillow'] = ProductCategory::firstOrCreate(
            ['slug' => 'pillow'],
            ['id' => (string) Str::uuid(), 'name' => 'Pillow']
        );

        $categories['topper'] = ProductCategory::firstOrCreate(
            ['slug' => 'topper'],
            ['id' => (string) Str::uuid(), 'name' => 'Topper']
        );

        return $categories;
    }

    private function determineCategory(string $segment1, array $categories): ?string
    {
        $beddingSegments = ['DV', 'HB', 'KM', 'SA', 'SB'];
        $protectorSegments = ['LR', 'ER'];
        $bolsterSegments = ['EB', 'EM'];

        if (in_array($segment1, $beddingSegments)) {
            return $categories['spring-bed']->id;
        }

        if (in_array($segment1, $protectorSegments)) {
            return $categories['mattress-protector']->id;
        }

        if (in_array($segment1, $bolsterSegments)) {
            return $categories['bolster']->id;
        }

        return $categories['spring-bed']->id;
    }

    private function getBrandName(string $code): string
    {
        $names = [
            'DV' => 'DVLA',
            'HB' => 'HBED',
            'KM' => 'KAMA',
            'LB' => 'LBOL',
            'LP' => 'LPILLOW',
            'LR' => 'LRP',
            'LT' => 'LTOP',
            'SA' => 'SABED',
            'SB' => 'SBE',
            'ER' => 'ERPRO',
            'EB' => 'EBBOL',
            'EM' => 'EMTRV',
        ];

        return $names[$code] ?? $code;
    }

    private function generateSlug(string $name): string
    {
        return Str::slug($name);
    }

    private function generateVariantName(array $item): string
    {
        $width = $item['segment5'] ?? '';
        $length = $item['segment6'] ?? '';

        if ($width && $length) {
            return "{$width} X {$length}";
        }

        return $item['description_line2'] ?? $item['description'];
    }
}