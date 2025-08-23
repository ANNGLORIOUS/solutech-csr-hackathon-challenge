<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManufacturerStock extends Model
{
    use HasFactory;

    protected $table = 'manufacturer_stock';

    protected $fillable = [
        'product_id',
        'quantity'
    ];

    // Relationships
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}