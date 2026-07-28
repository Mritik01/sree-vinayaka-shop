<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Admin::updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Shri Vinayak Admin',
                'email' => 'admin@vinayakfamilyshop.com',
                'password' => Hash::make('admin123'),
                'is_super_admin' => true,
            ]
        );
    }
}
