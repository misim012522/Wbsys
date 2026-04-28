<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DemoFeedback extends Model
{
    use HasFactory;

    protected $table = 'demo_feedbacks';

    protected $fillable = [
        'rating',
        'comment',
    ];
}
