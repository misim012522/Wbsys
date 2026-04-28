<?php

namespace App\Http\Controllers;

use App\Models\OtaTestNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/**
 * OTA Update Demo Controller
 * ──────────────────────────
 * Provides three endpoints for testing tenant-isolated OTA updates:
 *
 *  GET  /ota-demo          → Show status page (table exists? notes list?)
 *  POST /ota-demo          → Add a test note (only works post-update)
 *  DELETE /ota-demo/{id}   → Delete a note
 *
 * The key test:
 *  - BEFORE update → Schema::connection('tenant')->hasTable('ota_test_notes') === false
 *  - AFTER  update → table exists, notes can be added, isolated per tenant
 */
class OtaTestController extends Controller
{
    public function index(Request $request)
    {
        $tenant       = app()->bound('current_tenant') ? app('current_tenant') : null;
        $tableExists  = Schema::connection('tenant')->hasTable('ota_test_notes');
        $notes        = $tableExists ? OtaTestNote::orderByDesc('created_at')->get() : collect();

        return view('admin.ota-demo', compact('tenant', 'tableExists', 'notes'));
    }

    public function store(Request $request)
    {
        if (! Schema::connection('tenant')->hasTable('ota_test_notes')) {
            return back()->with('error', 'Table does not exist yet. Apply the OTA update first.');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'body'  => ['nullable', 'string', 'max:500'],
        ]);

        OtaTestNote::create([
            'title'      => $validated['title'],
            'body'       => $validated['body'] ?? null,
            'created_by' => auth()->user()->name ?? 'Unknown',
        ]);

        return back()->with('success', 'Note saved to this tenant\'s database.');
    }

    public function destroy(int $id)
    {
        if (! Schema::connection('tenant')->hasTable('ota_test_notes')) {
            return back()->with('error', 'Table does not exist yet.');
        }

        $note = OtaTestNote::findOrFail($id);
        $note->delete();

        return back()->with('success', 'Note deleted.');
    }
}
