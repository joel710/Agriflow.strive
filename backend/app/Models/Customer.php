<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'delivery_address',
        'food_preferences',
    ];

    /**
     * Get the user that owns the customer profile.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the orders for this customer.
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    // If favorites are linked to Customer model instead of User model
    // public function favoriteProducts()
    // {
    //     return $this->belongsToMany(Product::class, 'favorites', 'customer_id', 'product_id')->withTimestamps();
    // }
}
