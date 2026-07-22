<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat data Sekolah pertama (ID 1)
        $school = School::firstOrCreate([
            'id' => 1
        ], [
            'name' => 'SMK Negeri 1',
            'npsn' => '10800001',
        ]);

        // 2. Hubungkan user admin dengan school_id tersebut
         User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);
    }
}