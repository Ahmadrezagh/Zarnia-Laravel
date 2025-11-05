<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
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
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'last_name',
        'email',
        'password',
        'type',
        'phone',
        'profile_image',
        'otp_code',
        'otp_expires_at'
    ];

    public static $TYPES = [
        'SUPERADMIN',
        'ADMIN',
        'USER',
    ];
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
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

    public function roles()
    {
        return $this->belongsToMany(Role::class,'user_roles');
    }

    public function hasPermission(Permission $permission)
    {
        return $this->hasPermissionsThroughRole($permission);
    }

    protected function hasPermissionsThroughRole(Permission $permission)
    {
        foreach ($permission->roles as $role)
        {
            if($this->roles->contains($role)) return true;
        }
        return false;
    }

    public function scopeAdmins(Builder $query)
    {
        return $query->where('type' , '=', User::$TYPES[1]);
    }

    public function scopeUsers(Builder $query)
    {
        return $query->where('type' , '=', User::$TYPES[2])->orWhereNull('type');
    }

    public function hasRole(Role $role)
    {
        return $this->roles()->where('role_id', '=', $role->id)->exists();
    }

    public function isSuperadmin()
    {
        return($this->type == User::$TYPES[0]);
    }

    public function isAdmin()
    {
        return($this->type == User::$TYPES[1]);
    }

    public function isUser()
    {
        return($this->type == User::$TYPES[2]);
    }

    public function getProfileImageAttribute($value)
    {
        if($value){
            return url($value);
        }
        return url('uploads/profiles/default/user.png');
    }

    public function favoriteProducts()
    {
        return $this->belongsToMany(Product::class,'favorites');
    }

    public function shoppingCartItems()
    {
        return $this->hasMany(ShoppingCartItem::class);
    }

    public function totalShoppingCart()
    {
        $total = 0;
        foreach ($this->shoppingCartItems as $shoppingCartItem){
            $total = $total + ($shoppingCartItem->count * $shoppingCartItem->product->price);
        }
        return $total;
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function getFirstAddressAttribute()
    {
        $firstAddress = $this->addresses->first();
        return $firstAddress ? $firstAddress->address : '-';
    }

    public function getAllAddressesAttribute()
    {
        if ($this->addresses->isEmpty()) {
            return '-';
        }
        
        return $this->addresses->map(function($address) {
            return "({$address->receiver_name})- {$address->address}";
        })->implode("\n");
    }

    public function getFullNameAttribute()
    {
        return $this->name.' '.$this->last_name;
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function discounts()
    {
        return $this->morphToMany(Discount::class, 'discountable', 'discountables');
    }

    public function gifts()
    {
        return $this->discounts()
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }
}
