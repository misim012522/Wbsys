<?php

namespace App\Models;

use App\Models\Concerns\UsesOtaConnection;
use Illuminate\Database\Eloquent\Model;

class OtaAnnouncement extends Model
{
    use UsesOtaConnection;

    protected $table = 'ota_announcements';

    protected $fillable = ['content', 'priority', 'expires_at'];

    protected $casts = [
        'expires_at' => 'datetime',
    ];
}
