<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class GitHubReleaseService
{
    private string $owner;
    private string $repo;
    private ?string $token;

    public function __construct()
    {
        $this->owner = config('services.github.owner', env('GITHUB_OWNER'));
        $this->repo = config('services.github.repo', env('GITHUB_REPO'));
        $this->token = config('services.github.token', env('GITHUB_TOKEN'));
    }

    /**
     * Fetch the latest release from GitHub
     */
    public function fetchLatestRelease(): ?array
    {
        $cacheKey = "github_release_{$this->owner}_{$this->repo}";
        
        return Cache::remember($cacheKey, 3600, function () {
            $url = "https://api.github.com/repos/{$this->owner}/{$this->repo}/releases/latest";
            
            $response = Http::withHeaders([
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'Wbsys-Queueless',
            ])->when($this->token, fn ($http) => $http->withToken($this->token))
            ->withOptions(['verify' => app()->environment('local') ? false : true])
            ->get($url);

            if (! $response->successful()) {
                return null;
            }

            $data = $response->json();

            return [
                'tag_name' => $data['tag_name'] ?? null,
                'name' => $data['name'] ?? null,
                'body' => $data['body'] ?? null,
                'html_url' => $data['html_url'] ?? null,
                'published_at' => $data['published_at'] ?? null,
                'prerelease' => $data['prerelease'] ?? false,
                'draft' => $data['draft'] ?? false,
                'assets' => collect($data['assets'] ?? [])->map(fn ($asset) => [
                    'name' => $asset['name'] ?? null,
                    'browser_download_url' => $asset['browser_download_url'] ?? null,
                    'size' => $asset['size'] ?? 0,
                ])->toArray(),
            ];
        });
    }

    /**
     * Fetch all releases from GitHub
     */
    public function fetchAllReleases(): array
    {
        $url = "https://api.github.com/repos/{$this->owner}/{$this->repo}/releases";
        
        $response = Http::withHeaders([
            'Accept' => 'application/vnd.github.v3+json',
            'User-Agent' => 'Wbsys-Queueless',
        ])->when($this->token, fn ($http) => $http->withToken($this->token))
        ->withOptions(['verify' => app()->environment('local') ? false : true])
        ->get($url);

        if (! $response->successful()) {
            return [];
        }

        return collect($response->json())->map(fn ($release) => [
            'tag_name' => $release['tag_name'] ?? null,
            'name' => $release['name'] ?? null,
            'body' => $release['body'] ?? null,
            'html_url' => $release['html_url'] ?? null,
            'published_at' => $release['published_at'] ?? null,
            'prerelease' => $release['prerelease'] ?? false,
            'draft' => $release['draft'] ?? false,
            'assets' => collect($release['assets'] ?? [])->map(fn ($asset) => [
                'name' => $asset['name'] ?? null,
                'browser_download_url' => $asset['browser_download_url'] ?? null,
                'size' => $asset['size'] ?? 0,
            ])->toArray(),
        ])->toArray();
    }

    /**
     * Sync GitHub releases to app_versions table
     */
    public function syncReleasesToAppVersions(): int
    {
        $releases = $this->fetchAllReleases();
        $synced = 0;

        foreach ($releases as $release) {
            if ($release['draft'] || $release['prerelease']) {
                continue;
            }

            $version = ltrim($release['tag_name'] ?? '', 'v');
            
            if (! $version) {
                continue;
            }

            $asset = collect($release['assets'] ?? [])->first(fn ($item) => str_ends_with($item['name'] ?? '', '.zip'))
                ?? collect($release['assets'] ?? [])->first();
            $downloadUrl = $asset['browser_download_url'] ?? null;

            // Only sync releases that have a download URL (release assets)
            if (! $downloadUrl) {
                continue;
            }

            $appVersion = \App\Models\AppVersion::query()->updateOrCreate(
                ['version' => $version],
                [
                    'release_notes' => $release['body'] ?? '',
                    'released_at' => $release['published_at'] ?? now(),
                    'is_forced' => false,
                    'download_url' => $downloadUrl,
                ]
            );

            if ($appVersion->wasRecentlyCreated) {
                $synced++;
            }
        }

        // Clear cache
        Cache::forget("github_release_{$this->owner}_{$this->repo}");

        return $synced;
    }
}
