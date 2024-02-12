<?php

namespace Fpaipl\Brandy\Database\Seeders;

use Illuminate\Database\Seeder;
use Fpaipl\Brandy\Database\Seeders\StockSeeder;
use Fpaipl\Brandy\Database\Seeders\PartyUserSeeder;

class BrandyDatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
    */
    public function run(): void
    {
        $this->call([
            PartyUserSeeder::class,
        ]);
    }
}
