<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'wallet_id',
        'type',
        'amount',
        'description',
        // 'related_type', // If using morphs for related models
        // 'related_id',   // If using morphs for related models
        // 'order_id', // If specifically linking to orders
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'amount' => 'decimal:2',
    ];

    /**
     * Get the wallet that this transaction belongs to.
     */
    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Get the related model (e.g., Order) if using morphs.
     */
    // public function related()
    // {
    //    return $this->morphTo();
    // }

    /**
     * Get the order associated with this transaction (if applicable).
     */
    // public function order()
    // {
    //     return $this->belongsTo(Order::class);
    // }
}
