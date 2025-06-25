<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'notification_email',
        'notification_sms',
        'notification_app',
        'language',
        'theme',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'notification_email' => 'boolean',
        'notification_sms' => 'boolean',
        'notification_app' => 'boolean',
    ];

    /**
     * Get the user that these settings belong to.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
