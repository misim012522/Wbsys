# Tenant Workspace Setup Commands

This file contains all the commands needed to fully configure the system for tenant workspaces.

## Initial Setup Commands

Run these commands after deploying or when setting up a new environment:

```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Rebuild cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run database migrations (central database)
php artisan migrate --path=database/migrations/central

# Sync current app version to database
php artisan app:sync-current-version

# Fetch latest GitHub releases
php artisan github:sync-releases

# Backfill existing tenants with current version
php artisan tenants:backfill-versions
```

## Regular Maintenance Commands

Run these periodically to keep the system updated:

```bash
# Sync latest GitHub releases (also runs hourly via scheduler)
php artisan github:sync-releases

# Sync current app version to database
php artisan app:sync-current-version

# Clear caches if needed
php artisan cache:clear
php artisan config:clear
```

## Environment Configuration

Ensure these environment variables are set in `.env`:

```env
# GitHub Integration (for version tracking)
GITHUB_OWNER=your-github-username
GITHUB_REPO=your-repo-name
GITHUB_TOKEN=your-github-personal-access-token
GITHUB_WEBHOOK_SECRET=your-webhook-secret

# App Version (fallback if not set per-tenant)
APP_VERSION=v1.0.18
```

## Tenant-Specific Version Management

To set or update a specific tenant's version:

```bash
# Via artisan tinker
php artisan tinker
>>> $tenant = App\Models\Tenant::find(1);
>>> $tenant->update(['app_version' => 'v1.0.18']);
>>> exit
```

Or use the Central Dashboard:
1. Go to Central Dashboard
2. Click "Edit tenant" for the desired tenant
3. Set the "App Version" field
4. Save

## Database Migrations

For tenant-specific databases, migrations run automatically when a tenant is created or when you run:

```bash
# Run tenant migrations for all tenants
php artisan tenants:migrate

# Run tenant migrations for a specific tenant
php artisan tenants:migrate --tenant=1
```

## Queue Workers

Ensure queue workers are running for background jobs:

```bash
# Start queue worker
php artisan queue:work

# Or use supervisor for production
# See: https://laravel.com/docs/queues#supervisor-configuration
```

## Scheduler

Ensure the Laravel scheduler is running to handle automated tasks:

```bash
# Add to crontab
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

The scheduler automatically runs:
- `github:sync-releases` - Hourly sync of GitHub releases

## Webhook Configuration

For automatic GitHub release syncing:

1. Go to your GitHub repository settings
2. Navigate to Webhooks
3. Add a new webhook:
   - Payload URL: `https://your-domain.com/github/webhook` or `https://your-domain.com/api/github/webhook`
   - Content type: `application/json`
   - Secret: Use your `GITHUB_WEBHOOK_SECRET` value
   - Events: Select "Releases" (published, edited, created)

## Verification Commands

To verify the system is working correctly:

```bash
# Check app versions in database
php scripts/check_app_versions.php

# Fetch and display GitHub releases
php scripts/fetch_github_releases.php

# Check tenant versions
php artisan tinker
>>> App\Models\Tenant::all(['id', 'name', 'app_version']);
>>> exit
```

## Troubleshooting

If version is not displaying correctly:

```bash
# Clear view cache (sidebar version is cached)
php artisan view:clear

# Clear application cache
php artisan cache:clear

# Re-sync version
php artisan app:sync-current-version
```

If GitHub releases are not syncing:

```bash
# Check GitHub credentials in .env
php artisan tinker
>>> config('services.github');
>>> exit

# Manually trigger sync
php artisan github:sync-releases
```

## Production Deployment Checklist

Before deploying to production:

- [ ] Set all environment variables
- [ ] Run `php artisan config:cache`
- [ ] Run `php artisan route:cache`
- [ ] Run `php artisan view:cache`
- [ ] Run `php artisan migrate --path=database/migrations/central`
- [ ] Run `php artisan app:sync-current-version`
- [ ] Run `php artisan github:sync-releases`
- [ ] Run `php artisan tenants:backfill-versions`
- [ ] Configure GitHub webhook
- [ ] Set up Laravel scheduler in crontab
- [ ] Start queue workers
- [ ] Verify version display in tenant dashboards
