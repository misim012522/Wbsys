<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
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
        'appointment_type',
        'appointment_date',
        'appointment_time',
        'status',
        'purpose',
        'notes',
        'reference_code',
    ];

    /** Appointment type options (admin uses for reminders). */
    public static function appointmentTypeOptions(): array
    {
        return [
            '' => '— Select —',
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

        return $this->guest_contact ?? '—';
    }

    protected function casts(): array
    {
        return [
            'appointment_date' => 'date',
            'appointment_time' => 'datetime:H:i',
        ];
    }

    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_NO_SHOW = 'no_show';

    public function scopeToday($query)
    {
        return $query->where('appointment_date', today());
    }

    public function scopeUpcomingToday($query)
    {
        return $query->today()->whereIn('status', [self::STATUS_PENDING, self::STATUS_CONFIRMED]);
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
}
