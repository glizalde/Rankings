<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StagesSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('stages')->updateOrInsert(
            ['order' => 1],
            ['name' => 'Etapa 1', 'order' => 1, 'active' => true]
        );
    }
}