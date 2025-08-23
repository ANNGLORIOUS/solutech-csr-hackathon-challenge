<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VanStock extends Model
{
    use HasFactory;

    protected $table = 'van_stock';

    protected $fillable = [
        'user_id',
        'product_id',
        'quantity'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}