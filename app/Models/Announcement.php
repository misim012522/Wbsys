<?php

namespace App\Models;

use App\Models\Concerns\UsesOtaConnection;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use UsesOtaConnection;

    protected $fillable = [
        'title',
        'content',
        'type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
