<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QueueEntry extends Model
{
    use BelongsToTenant, UsesTenantConnection;

    protected $fillable = [
        'tenant_id',
        'office_id',
        'user_id',
        'guest_name',
        'guest_contact',
        'guest_email',
        'guest_phone',
        'service_type',
        'queue_number',
        'queue_date',
        'status',
        'called_at',
        'served_at',
        'notes',
        'reference_code',
    ];

    /** Service type options for queue (admin can use for reminders). */
    public static function serviceTypeOptions(): array
    {
        return [
            '' => '- Select -',
            'Transcript' => 'Transcript / Records',
            'Enrollment' => 'Enrollment',
            'Payment' => 'Payment / Fees',
            'Counseling' => 'Counseling / Advising',
            'Medical' => 'Medical / Clinic',
            'Other' => 'Other',
        ];
    }

    public function getContactForReminderAttribute(): string
    {
        if ($this->guest_email) {
            return $this->guest_email;
        }
        if ($this->guest_phone) {
            return $this->guest_phone;
        }

        return $this->guest_contact ?? '-';
    }

    protected function casts(): array
    {
        return [
            'queue_date' => 'date',
            'called_at' => 'datetime',
            'served_at' => 'datetime',
        ];
    }

    public const STATUS_WAITING = 'waiting';

    public const STATUS_CALLED = 'called';

    public const STATUS_SERVING = 'serving';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_NO_SHOW = 'no_show';

    public function scopeToday($query)
    {
        return $query->where('queue_date', today());
    }

    public function scopeActiveToday($query)
    {
        return $query->today()->whereIn('status', [self::STATUS_WAITING, self::STATUS_CALLED, self::STATUS_SERVING]);
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->guest_name ?? $this->user?->name ?? 'Guest';
    }

    public function getPositionInLineAttribute(): ?int
    {
        if (! in_array($this->status, [self::STATUS_WAITING, self::STATUS_CALLED, self::STATUS_SERVING])) {
            return null;
        }

        return $this->office->queueEntries()
            ->where('queue_date', $this->queue_date)
            ->whereIn('status', [self::STATUS_WAITING, self::STATUS_CALLED, self::STATUS_SERVING])
            ->where('queue_number', '<=', $this->queue_number)
            ->count();
    }
}
