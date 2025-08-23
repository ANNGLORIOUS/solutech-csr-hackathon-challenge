<?php

namespace Database\Seeders;

use App\Models\DistributorStock;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DistributorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         $json = file_get_contents('https://raw.githubusercontent.com/ANNGLORIOUS/solutech-csr-hackathon-challenge/refs/heads/main/seed_data.json');
        $data = json_decode($json, true);
  //    dd($data['stock']['distributor']);
        foreach($data['stock']['distributor'] as $stock){
            DistributorStock::updateOrCreate(
                ['user_id' => $stock['user_id']],
                $stock
            );
        }
       
    }
}
