<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'office_id',
        'user_id',
        'action',
        'description',
        'subject_type',
        'subject_id',
        'properties',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
        ];
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function log(
        int $officeId,
        string $action,
        string $description,
        ?int $userId = null,
        ?string $subjectType = null,
        ?int $subjectId = null,
        ?array $properties = null,
        ?string $ip = null
    ): self {
        $office = Office::find($officeId);
        $tenantId = $office?->tenant_id;
        return self::create([
            'tenant_id' => $tenantId,
            'office_id' => $officeId,
            'user_id' => $userId,
            'action' => $action,
            'description' => $description,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'properties' => $properties,
            'ip_address' => $ip ?? request()?->ip(),
        ]);
    }
}
