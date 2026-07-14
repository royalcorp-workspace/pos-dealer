<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('id_ID');

        // Let's create realistic provinces in Indonesia
        $provincesData = [
            ['code' => 'DKI', 'name' => 'DKI Jakarta'],
            ['code' => 'JABAR', 'name' => 'Jawa Barat'],
            ['code' => 'JATENG', 'name' => 'Jawa Tengah'],
            ['code' => 'JATIM', 'name' => 'Jawa Timur'],
            ['code' => 'BANTEN', 'name' => 'Banten'],
        ];

        // Cities mapping to Provinces
        $citiesData = [
            'DKI' => ['Jakarta Pusat', 'Jakarta Selatan', 'Jakarta Barat', 'Jakarta Utara', 'Jakarta Timur'],
            'JABAR' => ['Bandung', 'Bogor', 'Depok', 'Bekasi', 'Cirebon'],
            'JATENG' => ['Semarang', 'Surakarta', 'Yogyakarta', 'Magelang', 'Tegal'],
            'JATIM' => ['Surabaya', 'Malang', 'Sidoarjo', 'Gresik', 'Kediri'],
            'BANTEN' => ['Tangerang', 'Tangerang Selatan', 'Serang', 'Cilegon'],
        ];

        // Clean existing data first to prevent duplicate entries and maintain clean state
        DB::table('sub_districts')->delete();
        DB::table('cities')->delete();
        DB::table('provinces')->delete();

        foreach ($provincesData as $prov) {
            $provinceId = Str::uuid()->toString();

            DB::table('provinces')->insert([
                'id' => $provinceId,
                'name' => $prov['name'],
                'code' => $prov['code'],
                'is_active' => true,
                'sort_order' => 1,
                'deleted' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $cities = $citiesData[$prov['code']] ?? [];
            foreach ($cities as $cityName) {
                $cityId = Str::uuid()->toString();

                DB::table('cities')->insert([
                    'id' => $cityId,
                    'province_id' => $provinceId,
                    'province' => $prov['name'], // Set to province name to satisfy NOT NULL constraint
                    'name' => $cityName,
                    'is_active' => true,
                    'sort_order' => 1,
                    'deleted' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Create 3 subdistricts for each city using Faker
                for ($i = 0; $i < 3; $i++) {
                    $subDistrictId = Str::uuid()->toString();
                    $districtName = 'Kecamatan ' . $faker->firstNameMale;
                    $subDistrictName = 'Kelurahan ' . $faker->firstNameFemale;

                    DB::table('sub_districts')->insert([
                        'id' => $subDistrictId,
                        'province_id' => $provinceId,
                        'province' => $prov['name'], // Set to province name to satisfy NOT NULL constraint
                        'city_id' => $cityId,
                        'district' => $districtName,
                        'sub_district' => $subDistrictName,
                        'postal_code' => $faker->postcode,
                        'is_active' => true,
                        'sort_order' => 1,
                        'deleted' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }
}
