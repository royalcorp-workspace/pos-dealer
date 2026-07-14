<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            BrandCategorySeeder::class,
            ProductSeeder::class,
            JasonSeeder::class,
            PriceProductSettingSeeder::class,
            PaymentMethodSeeder::class,
            LocationSeeder::class,
            MattressProductSeeder::class,
            CourierSeeder::class,
        ]);

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
