<?php

namespace App\Models;

use App\Models\Concerns\UsesCentralConnection;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantSubscription extends Model
{
    use UsesCentralConnection;

    protected $fillable = ['tenant_id', 'plan_id', 'starts_at', 'ends_at', 'status'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime'];
    }

    public const STATUS_ACTIVE = 'active';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_TRIALING = 'trialing';

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function isActive(): bool
    {
        if (! in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_TRIALING])) {
            return false;
        }

        return ! $this->ends_at || ! $this->ends_at->isPast();
    }

    public static function calculateMonthlyEndAt(CarbonInterface $startsAt): CarbonInterface
    {
        return $startsAt->copy()->addMonthNoOverflow();
    }

    public static function expirePastDue(): int
    {
        return self::query()
            ->whereIn('status', [self::STATUS_ACTIVE, self::STATUS_TRIALING])
            ->whereNotNull('ends_at')
            ->where('ends_at', '<', Carbon::now())
            ->update(['status' => self::STATUS_EXPIRED]);
    }

    public static function backfillMissingMonthlyEndDates(): void
    {
        self::query()
            ->whereNull('ends_at')
            ->whereNotNull('starts_at')
            ->chunkById(100, function ($subscriptions): void {
                foreach ($subscriptions as $subscription) {
                    $startsAt = $subscription->starts_at;

                    if (! $startsAt) {
                        continue;
                    }

                    $subscription->forceFill([
                        'ends_at' => self::calculateMonthlyEndAt($startsAt),
                    ])->save();
                }
            });
    }
}
