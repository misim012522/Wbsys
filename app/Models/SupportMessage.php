<?php

namespace App\Models;

use App\Models\Concerns\UsesCentralConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportMessage extends Model
{
    use UsesCentralConnection;

    public const SENDER_TENANT = 'tenant';
    public const SENDER_CENTRAL = 'central';

    protected $fillable = [
        'thread_id',
        'sender_type',
        'sender_id',
        'sender_name',
        'sender_role',
        'message',
    ];

    public function thread(): BelongsTo
    {
        return $this->belongsTo(SupportThread::class, 'thread_id');
    }
}
