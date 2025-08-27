<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int,string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'van_capacity', // include van_capacity if van reps have it
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int,string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string,string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // --------------------
    // Relationships
    // --------------------

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

    // --------------------
    // Scopes
    // --------------------
    public function scopeReps($query)
    {
        return $query->where('role', 'van-rep');
    }

    public function scopeDistributors($query)
    {
        return $query->where('role', 'distributor');
    }

    public function scopeManagers($query)
    {
        return $query->where('role', 'manager');
    }

    // --------------------
    // Role helpers
    // --------------------
    public function isRep()
    {
        return strtolower($this->role) === 'van-rep';
    }

    public function isDistributor()
    {
        return strtolower($this->role) === 'distributor';
    }

    public function isManager()
    {
        return strtolower($this->role) === 'manager';
    }

    // --------------------
    // Van capacity helpers
    // --------------------
    public function getCurrentVanCapacity()
    {
        if (!$this->isRep()) {
            return 0;
        }

        return $this->vanStock()->sum('quantity');
    }

    public function canAddToVan($quantity)
    {
        if (!$this->isRep()) {
            return false;
        }

        return ($this->getCurrentVanCapacity() + $quantity) <= $this->van_capacity;
    }
}
