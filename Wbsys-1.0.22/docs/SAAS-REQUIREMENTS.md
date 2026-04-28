# Multi-Tenant SaaS Requirements (Implementation Summary)

This document maps the project requirements to the implementation.

## 1. Multi-Tenant
- **Tenants table**: Each tenant is an independent organization/customer using the system.
- **tenant_id** on: `users`, `offices`, `queue_entries`, `appointments`, `activity_logs`.
- One tenant has many users and offices; queue and appointments are scoped by tenant.
- Default tenant is created by migration/seed; new tenants can be added via `tenants` table or future admin UI.

## 2. Multi-User with RBAC
- **Roles**: `admin`, `office_staff`, `student` (system roles, `tenant_id` null).
- **Permissions**: e.g. `offices.manage`, `queue.manage`, `appointments.manage`, `reports.view`, `users.manage`, `office.serve`.
- **role_permission** pivot: which permission each role has.
- **User**: has `role` (string); `User::hasPermission($slug)` resolves role and checks permission.
- **Middleware**: `EnsurePermission` (optional); routes currently use `role:admin` and tenant-scoped data.

## 3. Customizable (design, functions)
- **Tenant settings** (JSON): `theme.primary_color`, `theme.logo_url`, `feature_flags` (array).
- **Tenant::getSetting()** / **setSetting()** for reading/writing.
- **Tenant::hasFeature($feature)** for plan-based or tenant-specific feature flags.
- Use `tenant->primary_color` or `tenant->logo_url` in layouts for branding.

## 4. Pricing Model (Basic, Pro, Ultimate, or module pricing)
- **Plans table**: `basic`, `pro`, `ultimate` with `price_monthly`, `price_yearly`, `features` (JSON).
- **Modules table**: optional add-on modules with `price_monthly`.
- **plan_modules**: which modules are included in each plan.
- **tenant_modules**: which extra modules a tenant has (module pricing).
- **tenant_subscriptions**: current plan and period per tenant.
- Seeder: `SaasSeeder` creates Basic, Pro, Ultimate plans.

## 5. Support and Updates (OTA updates)
- **app_versions** table: `version`, `release_notes`, `released_at`, `is_forced`, `download_url`.
- **GET /api/ota/check?version=1.0.0**: returns `update_available`, `version`, `release_notes`, `is_forced`, `download_url` when a newer version exists.
- **config('app.version')** (or `APP_VERSION`): current app version for OTA check.
- **Tenant**: `support_url` for support link per tenant.

## 6. Tenancy (domain / file system)
- **Domain**: `tenants.domain` (custom domain) and `tenants.subdomain` (e.g. `acme` for `acme.yourapp.com`).
- **ResolveTenant** middleware: resolves tenant from request host (domain or subdomain) and sets `current_tenant` / `current_tenant_id`.
- **File system**: `Tenant::storagePath($path)` returns `tenants/{id}/...` for tenant-isolated storage (use when saving uploads).

## 7. Tamper-Free & Data Isolation
- **EnsureTenantContext**: sets current tenant from authenticated user so all operations are in that tenant.
- **EnsureResourceBelongsToTenant** middleware: ensures route model (office, queueEntry, appointment, user) belongs to current user’s tenant; returns 403 otherwise.
- Admin routes for office, queueEntry, appointment, user use `tenant.resource` middleware.
- All admin queries for offices, queue, appointments, users are scoped by `tenant_id` when user has one.
