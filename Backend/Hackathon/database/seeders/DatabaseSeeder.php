<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Product;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $json = file_get_contents('https://raw.githubusercontent.com/ANNGLORIOUS/solutech-csr-hackathon-challenge/refs/heads/main/seed_data.json');
        $data = json_decode($json, true);
        dd($data);

        foreach ($data['users'] as $userData) {
            User::create($userData);
        }

        foreach ($data['products'] as $productData) {
            Product::create($productData);
        }


    }
}
