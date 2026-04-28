<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DemoHelpTicket extends Model
{
    protected $fillable = ['subject', 'description', 'status', 'user_name'];
}
