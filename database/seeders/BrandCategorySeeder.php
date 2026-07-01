<?php

namespace Database\Seeders;

use App\Models\Frontend\Brand;
use App\Models\Frontend\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BrandCategorySeeder extends Seeder
{
    public function run(): void
    {
        Brand::firstOrCreate(
            ['slug' => 'royal-brand'],
            ['id' => (string) Str::uuid(), 'name' => 'Royal Brand']
        );

        Category::firstOrCreate(
            ['slug' => 'spring-bed'],
            ['id' => (string) Str::uuid(), 'name' => 'Spring Bed']
        );

        Category::firstOrCreate(
            ['slug' => 'pillow'],
            ['id' => (string) Str::uuid(), 'name' => 'Pillow']
        );
    }
}
