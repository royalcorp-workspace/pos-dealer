<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CourierSeeder extends Seeder
{
    public function run(): void
    {
        $couriers = [
            [
                'code' => 'jne',
                'name' => 'JNE Express',
                'type' => 1,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'code' => 'jnt',
                'name' => 'J&T Express',
                'type' => 1,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'code' => 'sicepat',
                'name' => 'SiCepat Express',
                'type' => 1,
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'code' => 'pos',
                'name' => 'Pos Indonesia',
                'type' => 1,
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'code' => 'tiki',
                'name' => 'TIKI',
                'type' => 1,
                'is_active' => true,
                'sort_order' => 5,
            ],
        ];

        foreach ($couriers as $courier) {
            $exists = DB::table('couriers')->where('code', $courier['code'])->first();
            if ($exists) {
                DB::table('couriers')->where('code', $courier['code'])->update([
                    'name' => $courier['name'],
                    'type' => $courier['type'],
                    'is_active' => $courier['is_active'],
                    'sort_order' => $courier['sort_order'],
                    'deleted' => false,
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('couriers')->insert(array_merge($courier, [
                    'id' => (string) Str::uuid(),
                    'deleted' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }
}
