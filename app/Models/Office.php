<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Office extends Model
{
    use BelongsToTenant, HasFactory, UsesTenantConnection;

    protected $fillable = [
        'name',
        'slug',
        'tenant_id',
        'description',
        'location',
        'is_active',
        'max_daily_queue',
        'serving_time_minutes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(OfficeSchedule::class);
    }

    public function queueEntries(): HasMany
    {
        return $this->hasMany(QueueEntry::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function staff(): HasMany
    {
        return $this->hasMany(User::class, 'office_id');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrderedByName($query)
    {
        return $query->orderBy('name');
    }

    public function getTodayQueueCountAttribute(): int
    {
        return $this->queueEntries()
            ->where('queue_date', today())
            ->whereIn('status', ['waiting', 'called', 'serving'])
            ->count();
    }
}
