<?php

namespace Database\Seeders;

use App\Services\Api\Tahesab;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tahesab = new Tahesab();
        $tahesab->getEtiketsAndStore(1,1000);
    }
}
