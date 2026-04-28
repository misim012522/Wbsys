<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DemoFaq extends Model
{
    use HasFactory;

    protected $table = 'demo_faqs';

    protected $fillable = [
        'question',
        'answer',
    ];
}
