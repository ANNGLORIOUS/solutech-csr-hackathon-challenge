<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ManufacturerStock;

class ManufacturerStockSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         $json = file_get_contents('https://raw.githubusercontent.com/ANNGLORIOUS/solutech-csr-hackathon-challenge/refs/heads/main/seed_data.json');
        $data = json_decode($json, true);
     // dd($data['stock']['manufacturer']);
        foreach($data['stock']['manufacturer'] as $stock){
            ManufacturerStock::updateOrCreate(
                ['product_id' => $stock['product_id']],
                $stock
            );
        }
    }
}
