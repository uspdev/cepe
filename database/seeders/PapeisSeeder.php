<?php

namespace Database\Seeders;

use App\Support\Papeis;
use Illuminate\Database\Seeder;

class PapeisSeeder extends Seeder
{
    public function run(): void
    {
        Papeis::sincronizar();
    }
}
