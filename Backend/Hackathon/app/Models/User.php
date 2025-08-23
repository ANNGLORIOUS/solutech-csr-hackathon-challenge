<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;


class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;
    

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    // Van Rep relationships
public function vanStock()
{
    return $this->hasMany(VanStock::class);
}

public function vanRequisitions()
{
    return $this->hasMany(Requisition::class, 'van_rep_id');
}

// Distributor relationships
public function distributorStock()
{
    return $this->hasMany(DistributorStock::class);
}

public function distributorRequisitions()
{
    return $this->hasMany(Requisition::class, 'distributor_id');
}

// Stock movements
public function stockMovementsFrom()
{
    return $this->hasMany(StockMovement::class, 'from_user_id');
}

public function stockMovementsTo()
{
    return $this->hasMany(StockMovement::class, 'to_user_id');
}

// Helper methods for role-based queries
public function scopeReps($query)
{
    return $query->where('role', 'Rep');
}

public function scopeDistributors($query)
{
    return $query->where('role', 'Distributor');
}

public function scopeManufacturers($query)
{
    return $query->where('role', 'Manufacturer');
}

// Check if user is a representative
public function isRep()
{
    return $this->role === 'Rep';
}

// Check if user is a distributor
public function isDistributor()
{
    return $this->role === 'Distributor';
}

// Check if user is a manufacturer
public function isManufacturer()
{
    return $this->role === 'Manufacturer';
}

// Get current van capacity usage for van reps
public function getCurrentVanCapacity()
{
    if (!$this->isRep()) {
        return 0;
    }
    
    return $this->vanStock()->sum('quantity');
}

// Check if van rep can add more stock
public function canAddToVan($quantity)
{
    if (!$this->isRep()) {
        return false;
    }
    
    return ($this->getCurrentVanCapacity() + $quantity) <= $this->van_capacity;
}
}
