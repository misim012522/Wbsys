<?php

namespace App\Models;

use App\Models\Concerns\UsesCentralConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class SupportThread extends Model
{
    use UsesCentralConnection;

    public const TYPE_SUPPORT = 'support';
    public const TYPE_ANNOUNCEMENT = 'announcement';

    public const STATUS_OPEN = 'open';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_RESOLVED = 'resolved';

    protected $fillable = [
        'tenant_id',
        'thread_type',
        'subject',
        'status',
        'last_message_at',
        'tenant_last_read_at',
        'central_last_read_at',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
            'tenant_last_read_at' => 'datetime',
            'central_last_read_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportMessage::class, 'thread_id')->orderBy('created_at');
    }

    public function markReadForTenant(): void
    {
        $this->forceFill(['tenant_last_read_at' => now()])->save();
    }

    public function markReadForCentral(): void
    {
        $this->forceFill(['central_last_read_at' => now()])->save();
    }

    public function hasUnreadForTenant(): bool
    {
        return $this->messages()
            ->where('sender_type', SupportMessage::SENDER_CENTRAL)
            ->when($this->tenant_last_read_at, fn ($query) => $query->where('created_at', '>', $this->tenant_last_read_at))
            ->exists();
    }

    public function hasUnreadForCentral(): bool
    {
        return $this->messages()
            ->where('sender_type', SupportMessage::SENDER_TENANT)
            ->when($this->central_last_read_at, fn ($query) => $query->where('created_at', '>', $this->central_last_read_at))
            ->exists();
    }

    public function isAnnouncement(): bool
    {
        return $this->thread_type === self::TYPE_ANNOUNCEMENT;
    }

    public static function supportTablesExist(): bool
    {
        $connection = app()->environment('testing') ? config('database.default') : 'central';

        return Schema::connection($connection)->hasTable('support_threads')
            && Schema::connection($connection)->hasTable('support_messages');
    }

    public static function unreadCountForTenant(?int $tenantId): int
    {
        if (! $tenantId || ! self::supportTablesExist()) {
            return 0;
        }

        return self::query()
            ->where('tenant_id', $tenantId)
            ->get()
            ->filter(fn (self $thread) => $thread->hasUnreadForTenant())
            ->count();
    }

    public static function unreadCountForCentral(): int
    {
        if (! self::supportTablesExist()) {
            return 0;
        }

        return self::query()
            ->get()
            ->filter(fn (self $thread) => $thread->hasUnreadForCentral())
            ->count();
    }
}
