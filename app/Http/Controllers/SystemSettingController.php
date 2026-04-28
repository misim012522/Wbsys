<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SystemSettingController extends Controller
{
    public function index(): JsonResponse
    {
        $settings = SystemSetting::all();
        return response()->json([
            'settings' => $settings,
        ]);
    }

    public function show(string $key): JsonResponse
    {
        $setting = SystemSetting::where('key', $key)->first();
        if (! $setting) {
            return response()->json(['error' => 'Setting not found'], 404);
        }
        return response()->json(['setting' => $setting]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'key' => ['required', 'string', 'unique:system_settings,key'],
            'value' => ['nullable', 'string'],
            'type' => ['required', 'in:string,integer,boolean,json'],
            'description' => ['nullable', 'string'],
        ]);

        $setting = SystemSetting::create($validated);

        return response()->json([
            'message' => 'System setting created successfully',
            'setting' => $setting,
        ], 201);
    }

    public function update(Request $request, SystemSetting $setting): JsonResponse
    {
        $validated = $request->validate([
            'value' => ['nullable', 'string'],
            'type' => ['sometimes', 'in:string,integer,boolean,json'],
            'description' => ['nullable', 'string'],
        ]);

        $setting->update($validated);

        return response()->json([
            'message' => 'System setting updated successfully',
            'setting' => $setting,
        ]);
    }

    public function destroy(SystemSetting $setting): JsonResponse
    {
        $setting->delete();
        return response()->json([
            'message' => 'System setting deleted successfully',
        ]);
    }
}
