<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnitsSeeder extends Seeder
{
    public function run(): void
    {
        $names = ['Azcapotzalco','Cuajimalpa','Iztapalapa','Lerma','Xochimilco'];

        foreach ($names as $name) {
            DB::table('units')->updateOrInsert(['name' => $name], ['name' => $name]);
        }
    }
}