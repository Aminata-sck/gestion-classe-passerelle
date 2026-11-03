<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /** Données de référence uniquement. Démonstration : php artisan db:seed --class=DemoSeeder */
    public function run(): void
    {
        $this->call(ReferenceSeeder::class);
    }
}
