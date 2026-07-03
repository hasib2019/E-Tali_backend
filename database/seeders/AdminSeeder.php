<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        Admin::updateOrCreate(
            ['email' => 'admin@talikhata.app'],
            ['name' => 'Tali Khata Admin', 'password' => 'password'],
        );
    }
}
