<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class RankingsSeeder extends Seeder
{
    public function run(): void
    {
        $name = 'UI GreenMetric';
        DB::table('rankings')->updateOrInsert(
            ['slug' => Str::slug($name)],
            ['name' => $name, 'slug' => Str::slug($name), 'active' => true]
        );
    }
}