<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'status',
        'tracking_number',
        'estimated_delivery_date',
        'actual_delivery_date',
        'delivery_person_name',
        'delivery_person_phone',
        'delivery_notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'estimated_delivery_date' => 'datetime',
        'actual_delivery_date' => 'datetime',
    ];

    /**
     * Get the order associated with this delivery.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
