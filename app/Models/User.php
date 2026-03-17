<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use BelongsToTenant, HasFactory, MustVerifyEmailTrait, Notifiable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_OFFICE_STAFF = 'office_staff';
    public const ROLE_STUDENT = 'student';

    protected $fillable = [
        'name',
        'username',
        'email',
        'phone',
        'password',
        'role',
        'tenant_id',
        'office_id',
        'student_id',
        'approved_at',
        'archived_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'approved_at' => 'datetime',
            'archived_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function queueEntries(): HasMany
    {
        return $this->hasMany(QueueEntry::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isOfficeStaff(): bool
    {
        return $this->role === self::ROLE_OFFICE_STAFF;
    }

    public function isStudent(): bool
    {
        return $this->role === self::ROLE_STUDENT;
    }

    public function isApproved(): bool
    {
        return $this->approved_at !== null;
    }

    public function isPending(): bool
    {
        return $this->approved_at === null;
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    public function scopeNotArchived($query)
    {
        return $query->whereNull('archived_at');
    }

    public function scopeArchived($query)
    {
        return $query->whereNotNull('archived_at');
    }

    public function hasPermission(string $permissionSlug): bool
    {
        $role = Role::bySlug($this->role)->forTenant($this->tenant_id)->first();
        return $role ? $role->hasPermission($permissionSlug) : false;
    }
}
