<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MattressProductSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create or Find Brand (Royal Foam)
        $brandId = '019f5935-9e00-7256-81e8-23c114d2eaa4';
        $brandExists = DB::table('brands')->where('slug', 'royal-foam')->first();
        if (!$brandExists) {
            DB::table('brands')->insert([
                'id' => $brandId,
                'name' => 'Royal Foam',
                'slug' => 'royal-foam',
                'description' => 'Produsen kasur busa dan spring bed berkualitas premium dengan garansi jangka panjang.',
                'status' => true,
                'deleted' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $brandId = $brandExists->id;
        }

        // 2. Create or Find Category (Kasur & Spring Bed)
        $categoryId = '019f5935-9e01-7256-81e8-23c114d2eaa4';
        $categoryExists = DB::table('product_category')->where('slug', 'kasur-spring-bed')->first();
        if (!$categoryExists) {
            DB::table('product_category')->insert([
                'id' => $categoryId,
                'name' => 'Kasur & Spring Bed',
                'slug' => 'kasur-spring-bed',
                'description' => 'Koleksi kasur orthopedic, spring bed, dan kasur busa berkualitas tinggi untuk kenyamanan tidur Anda.',
                'is_active' => true,
                'sort_order' => 1,
                'deleted' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $categoryId = $categoryExists->id;
        }

        // 3. Define 5 products data
        $productsData = [
            [
                'id' => '019f5935-9e02-7256-81e8-23c114d2eaa4',
                'name' => 'Kasur Orthopedic Spring Bed Premium',
                'slug' => 'kasur-orthopedic-spring-bed-premium',
                'thumbnail' => 'products/premium_mattress.jpg',
                'short_description' => 'Kasur kesehatan orthopedic dengan sistem pegas pocket spring independen untuk menyokong tulang belakang secara sempurna.',
                'base_price' => 4500000.00,
                'variants' => [
                    [
                        'id' => '019f5935-9e03-7256-81e8-23c114d2eaa4',
                        'variant_name' => 'King Size (180x200)',
                        'sku' => 'KSR-ORTHO-180',
                        'price' => 5000000.00,
                        'stock_quantity' => 15,
                        'attributes' => json_encode(['width' => 180, 'length' => 200, 'height' => 30, 'weight' => 45]),
                    ],
                    [
                        'id' => '019f5935-9e04-7256-81e8-23c114d2eaa4',
                        'variant_name' => 'Queen Size (160x200)',
                        'sku' => 'KSR-ORTHO-160',
                        'price' => 4500000.00,
                        'stock_quantity' => 20,
                        'attributes' => json_encode(['width' => 160, 'length' => 200, 'height' => 30, 'weight' => 40]),
                    ],
                    [
                        'id' => '019f5935-9e05-7256-81e8-23c114d2eaa4',
                        'variant_name' => 'Single Size (120x200)',
                        'sku' => 'KSR-ORTHO-120',
                        'price' => 3500000.00,
                        'stock_quantity' => 10,
                        'attributes' => json_encode(['width' => 120, 'length' => 200, 'height' => 30, 'weight' => 30]),
                    ],
                ]
            ],
            [
                'id' => '019f5935-9e12-7256-81e8-23c114d2eaa4',
                'name' => 'Kasur Latex Hybrid Ergonomic Comfort',
                'slug' => 'kasur-latex-hybrid-ergonomic-comfort',
                'thumbnail' => 'products/latex_mattress.jpg',
                'short_description' => 'Kasur latex alami berpadu dengan memory foam higienis untuk kesejukan ekstra dan kontur tubuh yang pas.',
                'base_price' => 6000000.00,
                'variants' => [
                    [
                        'id' => '019f5935-9e13-7256-81e8-23c114d2eaa4',
                        'variant_name' => 'King Size (180x200)',
                        'sku' => 'KSR-LATEX-180',
                        'price' => 6500000.00,
                        'stock_quantity' => 12,
                        'attributes' => json_encode(['width' => 180, 'length' => 200, 'height' => 32, 'weight' => 50]),
                    ],
                    [
                        'id' => '019f5935-9e14-7256-81e8-23c114d2eaa4',
                        'variant_name' => 'Queen Size (160x200)',
                        'sku' => 'KSR-LATEX-160',
                        'price' => 6000000.00,
                        'stock_quantity' => 18,
                        'attributes' => json_encode(['width' => 160, 'length' => 200, 'height' => 32, 'weight' => 45]),
                    ],
                ]
            ],
            [
                'id' => '019f5935-9e22-7256-81e8-23c114d2eaa4',
                'name' => 'Kasur Busa Royal Foam Density 23',
                'slug' => 'kasur-busa-royal-foam-density-23',
                'thumbnail' => 'products/latex_mattress.jpg',
                'short_description' => 'Kasur busa dengan kepadatan tinggi anti kempes berteknologi sanitized anti bakteri dan jamur.',
                'base_price' => 1500000.00,
                'variants' => [
                    [
                        'id' => '019f5935-9e23-7256-81e8-23c114d2eaa4',
                        'variant_name' => 'Double Size (140x200)',
                        'sku' => 'KSR-FOAM-140',
                        'price' => 1800000.00,
                        'stock_quantity' => 25,
                        'attributes' => json_encode(['width' => 140, 'length' => 200, 'height' => 20, 'weight' => 20]),
                    ],
                    [
                        'id' => '019f5935-9e24-7256-81e8-23c114d2eaa4',
                        'variant_name' => 'Single Size (120x200)',
                        'sku' => 'KSR-FOAM-120',
                        'price' => 1500000.00,
                        'stock_quantity' => 30,
                        'attributes' => json_encode(['width' => 120, 'length' => 200, 'height' => 20, 'weight' => 18]),
                    ],
                ]
            ],
            [
                'id' => '019f5935-9e32-7256-81e8-23c114d2eaa4',
                'name' => 'Kasur Spring Bed King Koil Royal Ortho',
                'slug' => 'kasur-spring-bed-king-koil-royal-ortho',
                'thumbnail' => 'products/premium_mattress.jpg',
                'short_description' => 'Kasur spring bed premium King Koil edisi spesial Royal Ortho untuk tidur mewah bagai hotel bintang lima.',
                'base_price' => 8500000.00,
                'variants' => [
                    [
                        'id' => '019f5935-9e33-7256-81e8-23c114d2eaa4',
                        'variant_name' => 'Super King Size (200x200)',
                        'sku' => 'KSR-KK-200',
                        'price' => 9500000.00,
                        'stock_quantity' => 8,
                        'attributes' => json_encode(['width' => 200, 'length' => 200, 'height' => 35, 'weight' => 60]),
                    ],
                    [
                        'id' => '019f5935-9e34-7256-81e8-23c114d2eaa4',
                        'variant_name' => 'King Size (180x200)',
                        'sku' => 'KSR-KK-180',
                        'price' => 8500000.00,
                        'stock_quantity' => 12,
                        'attributes' => json_encode(['width' => 180, 'length' => 200, 'height' => 35, 'weight' => 55]),
                    ],
                ]
            ],
            [
                'id' => '019f5935-9e42-7256-81e8-23c114d2eaa4',
                'name' => 'Kasur Busa Lipat Travel Portable',
                'slug' => 'kasur-busa-lipat-travel-portable',
                'thumbnail' => 'products/latex_mattress.jpg',
                'short_description' => 'Kasur busa lipat 3 praktis dan hemat tempat untuk travel, piknik, kasur tamu, atau kasur lantai.',
                'base_price' => 750000.00,
                'variants' => [
                    [
                        'id' => '019f5935-9e43-7256-81e8-23c114d2eaa4',
                        'variant_name' => 'Single Lipat (90x190)',
                        'sku' => 'KSR-LIPAT-90',
                        'price' => 750000.00,
                        'stock_quantity' => 50,
                        'attributes' => json_encode(['width' => 90, 'length' => 190, 'height' => 10, 'weight' => 8]),
                    ],
                ]
            ]
        ];

        // 4. Clean up previous records to prevent duplicates
        $productIds = array_column($productsData, 'id');
        DB::table('products')->whereIn('id', $productIds)->delete();
        DB::table('product_variants')->whereIn('product_id', $productIds)->delete();

        // 5. Create Price Setting (15% Direct Discount)
        $promoId = '019f5935-9e06-7256-81e8-23c114d2eaa4';
        DB::table('price_product_settings')->where('code', 'PROMO_KASUR_MERDEKA')->delete();
        DB::table('price_product_settings')->insert([
            'id' => $promoId,
            'code' => 'PROMO_KASUR_MERDEKA',
            'title' => 'Promo Spesial Kasur Orthopedic & Busa',
            'description' => 'Hemat 15% untuk pembelian koleksi kasur premium Royal Foam. Promo terbatas!',
            'type' => 1, // Direct discount
            'scope' => 2, // Specific products
            'discount_type' => 1, // Percentage
            'discount_value' => 15.00,
            'is_active' => true,
            'is_featured' => true,
            'sort_order' => 1,
            'deleted' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('price_product_setting_items')->where('price_product_setting_id', $promoId)->delete();

        // 6. Seed each product and attach to promo
        foreach ($productsData as $pData) {
            DB::table('products')->insert([
                'id' => $pData['id'],
                'brand_id' => $brandId,
                'category_id' => $categoryId,
                'name' => $pData['name'],
                'slug' => $pData['slug'],
                'thumbnail' => $pData['thumbnail'],
                'alt_text' => $pData['name'],
                'short_description' => $pData['short_description'],
                'description' => '<p>' . $pData['short_description'] . ' Dirancang khusus dengan kenyamanan tingkat tinggi untuk menyempurnakan kualitas tidur Anda.</p>',
                'base_price' => $pData['base_price'],
                'best_seller' => true,
                'is_new' => true,
                'sort_order' => 1,
                'status' => true,
                'deleted' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($pData['variants'] as $vData) {
                DB::table('product_variants')->insert([
                    'id' => $vData['id'],
                    'product_id' => $pData['id'],
                    'variant_name' => $vData['variant_name'],
                    'sku' => $vData['sku'],
                    'price' => $vData['price'],
                    'stock_quantity' => $vData['stock_quantity'],
                    'attributes' => $vData['attributes'],
                    'deleted' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Attach to 15% discount promo
            DB::table('price_product_setting_items')->insert([
                'price_product_setting_id' => $promoId,
                'product_id' => $pData['id'],
                'discount_type' => 1,
                'discount_value' => 15.00,
                'deleted' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
