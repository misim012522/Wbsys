<?php

namespace App\Http\Controllers;

use App\Services\GitHubReleaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GitHubWebhookController extends Controller
{
    public function handle(Request $request, GitHubReleaseService $githubService): JsonResponse
    {
        $secret = config('services.github.webhook_secret');
        $signature = $request->header('X-Hub-Signature-256');
        $payload = $request->getContent();

        // Verify webhook signature if secret is configured
        if ($secret && $signature) {
            $hash = 'sha256='.hash_hmac('sha256', $payload, $secret);
            if (! hash_equals($hash, $signature)) {
                Log::warning('Invalid GitHub webhook signature');
                return response()->json(['error' => 'Invalid signature'], 401);
            }
        }

        $event = $request->header('X-GitHub-Event');

        // Handle release events
        if ($event === 'release') {
            $action = $request->input('action');
            
            if (in_array($action, ['published', 'edited', 'created'])) {
                try {
                    $synced = $githubService->syncReleasesToAppVersions();
                    Log::info("GitHub release synced: {$synced} versions updated");
                    
                    return response()->json([
                        'message' => 'Release synced successfully',
                        'synced' => $synced,
                    ]);
                } catch (\Throwable $e) {
                    Log::error('Failed to sync GitHub release: '.$e->getMessage());
                    return response()->json(['error' => 'Sync failed'], 500);
                }
            }
        }

        return response()->json(['message' => 'Event received']);
    }
}
