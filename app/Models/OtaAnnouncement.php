<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtaAnnouncement extends Model
{
    protected $connection = 'tenant';
    protected $table = 'ota_announcements';

    protected $fillable = ['content', 'priority', 'expires_at'];

    protected $casts = [
        'expires_at' => 'datetime',
    ];
}
