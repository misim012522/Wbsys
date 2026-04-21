<?php

namespace App\Console\Commands;

use App\Models\AppVersion;
use Carbon\Carbon;
use Illuminate\Console\Command;

class PublishAppVersion extends Command
{
    protected $signature = 'app-version:publish
        {version? : Version to publish (defaults to APP_VERSION / config(app.version))}
        {--notes= : Release notes for this version}
        {--download-url= : Download URL for this version}
        {--released-at= : Release timestamp (defaults to now)}
        {--force-update : Mark this release as a forced update}';

    protected $description = 'Publish the current application version into the app_versions table for OTA update checks.';

    public function handle(): int
    {
        $version = (string) ($this->argument('version') ?: config('app.version', '1.0.0'));
        $normalizedVersion = AppVersion::normalizeVersion($version);

        if (! $normalizedVersion) {
            $this->components->error('The provided version is invalid.');

            return self::FAILURE;
        }

        $releasedAtInput = $this->option('released-at');
        $releasedAt = $releasedAtInput
            ? Carbon::parse($releasedAtInput)
            : now();

        $appVersion = AppVersion::query()->updateOrCreate(
            ['version' => $version],
            [
                'release_notes' => $this->option('notes') ?: null,
                'released_at' => $releasedAt,
                'is_forced' => (bool) $this->option('force-update'),
                'download_url' => $this->option('download-url') ?: null,
            ]
        );

        $this->components->info("Published application version [{$appVersion->version}].");
        $this->line('Released at: '.$appVersion->released_at->toDateTimeString());
        $this->line('Forced update: '.($appVersion->is_forced ? 'yes' : 'no'));
        $this->line('Download URL: '.($appVersion->download_url ?: 'n/a'));

        return self::SUCCESS;
    }
}
