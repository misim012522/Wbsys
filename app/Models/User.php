<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\UsesTenantConnection;
use App\Notifications\TenantResetPasswordNotification;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use BelongsToTenant, HasFactory, MustVerifyEmailTrait, Notifiable, UsesTenantConnection;

    public const ROLE_SYSTEM_ADMIN = 'system_admin';

    public const ROLE_TENANT_ADMIN = 'tenant_admin';

    public const ROLE_OFFICE_STAFF = 'office_staff';

    public const ROLE_STUDENT = 'student';

    public const OFFICE_STAFF_PERMISSION_DEFINITIONS = [
        'office.dashboard' => [
            'setting' => 'rbac.office_staff.office.dashboard',
            'default' => true,
            'input' => 'office_staff_office_dashboard',
            'label' => 'Open office dashboard',
            'description' => 'Lets office staff open their main office workspace and view live queue and appointment summaries.',
            'badge' => 'emerald',
        ],
        'office.qr' => [
            'setting' => 'rbac.office_staff.office.qr',
            'default' => true,
            'input' => 'office_staff_office_qr',
            'label' => 'Use QR tools',
            'description' => 'Lets office staff open the QR page and download the office QR code for walk-in access.',
            'badge' => 'teal',
        ],
        'office.queue.manage' => [
            'setting' => 'rbac.office_staff.office.queue.manage',
            'default' => true,
            'input' => 'office_staff_office_queue_manage',
            'label' => 'Manage queue operations',
            'description' => 'Lets office staff call the next number and update queue statuses for their assigned office.',
            'badge' => 'amber',
        ],
        'office.appointments.manage' => [
            'setting' => 'rbac.office_staff.office.appointments.manage',
            'default' => true,
            'input' => 'office_staff_office_appointments_manage',
            'label' => 'Manage appointments',
            'description' => 'Lets office staff accept, complete, and cancel office appointments.',
            'badge' => 'rose',
        ],
        'office.activity.view' => [
            'setting' => 'rbac.office_staff.office.activity.view',
            'default' => true,
            'input' => 'office_staff_office_activity_view',
            'label' => 'View activity log',
            'description' => 'Lets office staff review office activity history and filter daily operations.',
            'badge' => 'slate',
        ],
        'reports.view' => [
            'setting' => 'rbac.office_staff.reports.view',
            'default' => true,
            'input' => 'office_staff_reports_view',
            'label' => 'View reports',
            'description' => 'Lets office staff open and download office reports when the tenant plan supports reports.',
            'badge' => 'sky',
        ],
    ];

    public const TENANT_ADMIN_PERMISSION_DEFINITIONS = [
        'admin.dashboard' => [
            'setting' => null,
            'default' => true,
            'input' => null,
            'label' => 'Open admin dashboard',
            'description' => 'Core tenant admin landing page access. This stays enabled to avoid locking the workspace out.',
            'badge' => 'emerald',
            'locked' => true,
        ],
        'admin.profile' => [
            'setting' => null,
            'default' => true,
            'input' => null,
            'label' => 'View admin profile',
            'description' => 'Lets the tenant admin view the built-in admin profile screen.',
            'badge' => 'teal',
            'locked' => true,
        ],
        'admin.office.manage' => [
            'setting' => 'rbac.tenant_admin.admin.office.manage',
            'default' => true,
            'input' => 'tenant_admin_admin_office_manage',
            'label' => 'Manage offices',
            'description' => 'Lets the tenant admin create, edit, and manage offices under the tenant workspace.',
            'badge' => 'amber',
        ],
        'admin.office.serve' => [
            'setting' => 'rbac.tenant_admin.admin.office.serve',
            'default' => true,
            'input' => 'tenant_admin_admin_office_serve',
            'label' => 'Use admin QR and serve tools',
            'description' => 'Lets the tenant admin open QR pages and directly serve queue and appointment operations from admin pages.',
            'badge' => 'rose',
        ],
        'users.manage' => [
            'setting' => 'rbac.tenant_admin.users.manage',
            'default' => true,
            'input' => 'tenant_admin_users_manage',
            'label' => 'Manage office staff accounts',
            'description' => 'Lets the tenant admin approve, archive, recover, and delete office staff accounts.',
            'badge' => 'sky',
        ],
        'reports.view' => [
            'setting' => 'rbac.tenant_admin.reports.view',
            'default' => true,
            'input' => 'tenant_admin_reports_view',
            'label' => 'View tenant reports',
            'description' => 'Lets the tenant admin open and download tenant-level reports.',
            'badge' => 'slate',
        ],
        'admin.customization.manage' => [
            'setting' => 'rbac.tenant_admin.admin.customization.manage',
            'default' => true,
            'input' => 'tenant_admin_admin_customization_manage',
            'label' => 'Manage customization',
            'description' => 'Lets the tenant admin edit tenant branding, labels, and workspace customization settings.',
            'badge' => 'teal',
        ],
        'admin.rbac.manage' => [
            'setting' => null,
            'default' => true,
            'input' => null,
            'label' => 'Manage access control',
            'description' => 'Lets the tenant admin update RBAC settings. This stays enabled to prevent accidental lockout.',
            'badge' => 'emerald',
            'locked' => true,
        ],
        'admin.settings.manage' => [
            'setting' => null,
            'default' => true,
            'input' => null,
            'label' => 'Manage admin settings',
            'description' => 'Lets the tenant admin update account and workspace settings. This stays enabled to preserve recovery access.',
            'badge' => 'emerald',
            'locked' => true,
        ],
    ];

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

    public function getConnectionName(): ?string
    {
        if ($this->connection) {
            return $this->connection;
        }

        if (app()->bound('current_tenant') || $this->tenant_id) {
            return 'tenant';
        }

        if (app()->environment('testing')) {
            return config('database.default');
        }

        return 'central';
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

    public function isSystemAdmin(): bool
    {
        return $this->role === self::ROLE_SYSTEM_ADMIN;
    }

    public function isTenantAdmin(): bool
    {
        return $this->role === self::ROLE_TENANT_ADMIN;
    }

    public function isAdmin(): bool
    {
        return $this->isTenantAdmin();
    }

    public function isOfficeStaff(): bool
    {
        return $this->role === self::ROLE_OFFICE_STAFF;
    }

    public function isStudent(): bool
    {
        return $this->role === self::ROLE_STUDENT;
    }

    public function isCentralUser(): bool
    {
        return $this->tenant_id === null;
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
        if ($this->isCentralUser()) {
            return true;
        }

        if ($this->isTenantAdmin()) {
            $tenant = app()->bound('current_tenant') ? app('current_tenant') : $this->tenant;
            $permissions = self::tenantAdminPermissionStates($tenant);

            if ($permissionSlug === 'office.serve') {
                return $permissions['admin.office.serve'] ?? false;
            }

            return $permissions[$permissionSlug] ?? false;
        }

        if ($this->isOfficeStaff()) {
            $tenant = app()->bound('current_tenant') ? app('current_tenant') : $this->tenant;
            $permissions = self::officeStaffPermissionStates($tenant);

            if ($permissionSlug === 'office.serve') {
                return ($permissions['office.dashboard'] ?? false)
                    || ($permissions['office.queue.manage'] ?? false)
                    || ($permissions['office.appointments.manage'] ?? false)
                    || ($permissions['office.qr'] ?? false)
                    || ($permissions['office.activity.view'] ?? false);
            }

            return $permissions[$permissionSlug] ?? false;
        }

        return false;
    }

    public function hasAnyPermission(string ...$permissionSlugs): bool
    {
        foreach ($permissionSlugs as $permissionSlug) {
            if ($this->hasPermission($permissionSlug)) {
                return true;
            }
        }

        return false;
    }

    public function dashboardRouteName(): string
    {
        if ($this->isCentralUser()) {
            return 'central.dashboard';
        }

        if ($this->isTenantAdmin()) {
            return 'admin.dashboard';
        }

        if ($this->isOfficeStaff()) {
            if ($this->hasPermission('office.dashboard')) {
                return 'office.dashboard';
            }

            if ($this->hasPermission('office.qr')) {
                return 'office.qr';
            }

            if ($this->hasPermission('reports.view')) {
                return 'office.reports';
            }

            if ($this->hasPermission('office.activity.view')) {
                return 'office.activity';
            }

            return 'tenant.settings.edit';
        }

        if ($this->isStudent()) {
            return 'tenant.home';
        }

        return 'login';
    }

    public function roleLabel(): string
    {
        if ($this->isCentralUser()) {
            return 'System Admin';
        }

        return match ($this->role) {
            self::ROLE_TENANT_ADMIN => 'Tenant Admin',
            self::ROLE_OFFICE_STAFF => 'Office Staff',
            self::ROLE_STUDENT => 'Student',
            default => str($this->role)->replace('_', ' ')->title()->toString(),
        };
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new TenantResetPasswordNotification($token));
    }

    public function resolveRouteBinding($value, $field = null): ?self
    {
        if (app()->bound('current_tenant')) {
            $this->setConnection('tenant');

            return $this->newQuery()
                ->where($field ?? $this->getRouteKeyName(), $value)
                ->first();
        }

        return parent::resolveRouteBinding($value, $field);
    }

    public static function officeStaffPermissionDefinitions(): array
    {
        return self::OFFICE_STAFF_PERMISSION_DEFINITIONS;
    }

    public static function tenantAdminPermissionDefinitions(): array
    {
        return self::TENANT_ADMIN_PERMISSION_DEFINITIONS;
    }

    public static function tenantAdminPermissionStates(?Tenant $tenant): array
    {
        $states = [];

        foreach (self::tenantAdminPermissionDefinitions() as $slug => $definition) {
            $default = $definition['default'] ?? false;
            $setting = $definition['setting'] ?? null;
            $states[$slug] = $setting === null
                ? (bool) $default
                : (bool) ($tenant?->getSetting($setting, $default) ?? $default);
        }

        return $states;
    }

    public static function officeStaffPermissionStates(?Tenant $tenant): array
    {
        $states = [];
        $legacyOfficeServe = $tenant?->getSetting('rbac.office_staff.office.serve');

        foreach (self::officeStaffPermissionDefinitions() as $slug => $definition) {
            $default = $definition['default'] ?? false;
            $states[$slug] = (bool) ($tenant?->getSetting($definition['setting'], $default) ?? $default);
        }

        if ($legacyOfficeServe === false) {
            foreach ([
                'office.dashboard',
                'office.qr',
                'office.queue.manage',
                'office.appointments.manage',
                'office.activity.view',
            ] as $slug) {
                $states[$slug] = false;
            }
        }

        return $states;
    }
}
