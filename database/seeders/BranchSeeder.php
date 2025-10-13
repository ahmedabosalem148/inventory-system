<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * من المواصفات: "النشاط: محل أدوات كهربائية لديه 3 مخازن (المصنع، العتبة، إمبابة)"
     */
    public function run(): void
    {
        $branches = [
            [
                'code' => 'FAC',
                'name' => 'المصنع',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'ATB',
                'name' => 'العتبة',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'IMB',
                'name' => 'إمبابة',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('branches')->insert($branches);
    }
}