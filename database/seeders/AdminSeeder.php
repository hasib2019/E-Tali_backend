<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        Admin::updateOrCreate(
            ['email' => 'hasib.9437.hu@gmail.com'],
            ['name' => 'Tali Khata Admin', 'password' => 'Rajbari@1234!!'],
        );
    }
}
