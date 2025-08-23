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
public function scopeVanReps($query)
{
    return $query->where('role', 'van_rep');
}

public function scopeDistributors($query)
{
    return $query->where('role', 'distributor');
}

public function scopeManufacturers($query)
{
    return $query->where('role', 'manufacturer');
}

// Check if user is a van rep
public function isVanRep()
{
    return $this->role === 'van_rep';
}

// Check if user is a distributor
public function isDistributor()
{
    return $this->role === 'distributor';
}

// Check if user is a manufacturer
public function isManufacturer()
{
    return $this->role === 'manufacturer';
}

// Get current van capacity usage for van reps
public function getCurrentVanCapacity()
{
    if (!$this->isVanRep()) {
        return 0;
    }
    
    return $this->vanStock()->sum('quantity');
}

// Check if van rep can add more stock
public function canAddToVan($quantity)
{
    if (!$this->isVanRep()) {
        return false;
    }
    
    return ($this->getCurrentVanCapacity() + $quantity) <= $this->van_capacity;
}
}
