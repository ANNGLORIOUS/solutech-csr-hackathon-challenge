<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'unit'
    ];

    // Relationships
    public function manufacturerStock()
    {
        return $this->hasMany(ManufacturerStock::class);
    }

    public function distributorStock()
    {
        return $this->hasMany(DistributorStock::class);
    }

    public function vanStock()
    {
        return $this->hasMany(VanStock::class);
    }

    public function requisitions()
    {
        return $this->hasMany(Requisition::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }
}