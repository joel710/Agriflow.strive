<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'producer_id',
        'name',
        'description',
        'price',
        'unit',
        'stock_quantity',
        'image_url',
        'is_bio',
        'is_available',
        // 'category', // if added as a direct field
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'price' => 'decimal:2',
        'stock_quantity' => 'integer',
        'is_bio' => 'boolean',
        'is_available' => 'boolean',
    ];

    /**
     * Get the producer that owns the product.
     */
    public function producer()
    {
        return $this->belongsTo(Producer::class);
    }

    /**
     * Get the order items for this product.
     */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * The users that have favorited this product.
     */
    public function favoritedByUsers()
    {
        return $this->belongsToMany(User::class, 'favorites', 'product_id', 'user_id')->withTimestamps();
    }

    // If favorites are linked to Customer model instead of User model
    // public function favoritedByCustomers()
    // {
    //     return $this->belongsToMany(Customer::class, 'favorites', 'product_id', 'customer_id')->withTimestamps();
    // }
}
