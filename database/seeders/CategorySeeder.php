<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * تصنيفات أدوات كهربائية أساسية
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'لمبات LED',
                'description' => 'لمبات إضاءة LED بجميع الأنواع والأحجام',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'مفاتيح كهربائية',
                'description' => 'مفاتيح إضاءة ومفاتيح توصيل',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'أسلاك كهربائية',
                'description' => 'كابلات وأسلاك توصيل كهربائية',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'قواطع كهربائية',
                'description' => 'قواطع حماية وتوزيع كهرباء',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'أباجورات ووحدات إضاءة',
                'description' => 'وحدات إضاءة ديكورية ومنزلية',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'أدوات تركيب',
                'description' => 'علب توصيل، دوسات، ومستلزمات التركيب',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('categories')->insert($categories);
    }
}
