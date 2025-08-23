<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'from_role',
        'from_user_id',
        'to_role',
        'to_user_id',
        'quantity',
        'date'
    ];

    protected $casts = [
        'date' => 'date'
    ];

    const ROLE_MANUFACTURER = 'manufacturer';
    const ROLE_DISTRIBUTOR = 'distributor';
    const ROLE_VAN_REP = 'van_rep';

    // Relationships
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser()
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }
}