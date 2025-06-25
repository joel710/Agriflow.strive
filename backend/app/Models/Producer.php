<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Producer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'farm_name',
        'siret',
        'experience_years',
        'farm_type',
        'surface_hectares',
        'farm_address',
        'certifications',
        'delivery_availability',
        'farm_description',
        'farm_photo_url',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'experience_years' => 'integer',
        'surface_hectares' => 'decimal:2',
        // 'certifications' => 'array', // If stored as JSON
    ];

    /**
     * Get the user that owns the producer profile.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the products for this producer.
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
