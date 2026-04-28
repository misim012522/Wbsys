<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DemoSystemLog extends Model
{
    use HasFactory;

    protected $table = 'demo_system_logs';

    protected $fillable = [
        'event',
        'user_name',
    ];
}
