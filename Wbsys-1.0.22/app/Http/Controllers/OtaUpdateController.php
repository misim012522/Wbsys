<?php

namespace App\Http\Controllers;

use App\Models\AppVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Support and Updates (OTA): check for new app version. */
class OtaUpdateController extends Controller
{
    public function check(Request $request): JsonResponse
    {
        $currentVersion = $request->input('version', config('app.version', '1.0.0'));
        $latest = AppVersion::latest()->first();

        if (! $latest || ! $latest->isNewerThan($currentVersion)) {
            return response()->json([
                'update_available' => false,
                'current_version' => $currentVersion,
            ]);
        }

        return response()->json([
            'update_available' => true,
            'version' => $latest->version,
            'release_notes' => $latest->release_notes,
            'released_at' => $latest->released_at->toIso8601String(),
            'is_forced' => $latest->is_forced,
            'download_url' => $latest->download_url,
        ]);
    }
}
