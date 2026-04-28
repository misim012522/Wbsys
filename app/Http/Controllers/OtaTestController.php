<?php

namespace App\Http\Controllers;

use App\Models\OtaTestNote;
use App\Models\OtaAnnouncement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/**
 * OTA Update Demo Controller
 * ──────────────────────────
 * Provides endpoints for testing tenant-isolated OTA updates.
 */
class OtaTestController extends Controller
{
    public function index(Request $request)
    {
        $tenant            = app()->bound('current_tenant') ? app('current_tenant') : null;
        
        $notesTableExists  = Schema::connection('tenant')->hasTable('ota_test_notes');
        $notes             = $notesTableExists ? OtaTestNote::orderByDesc('created_at')->get() : collect();

        $annTableExists    = Schema::connection('tenant')->hasTable('ota_announcements');
        $announcements     = $annTableExists ? OtaAnnouncement::orderByDesc('created_at')->get() : collect();

        return view('admin.ota-demo', compact('tenant', 'notesTableExists', 'notes', 'annTableExists', 'announcements'));
    }

    public function storeAnnouncement(Request $request)
    {
        if (! Schema::connection('tenant')->hasTable('ota_announcements')) {
            return back()->with('error', 'Announcements table does not exist yet.');
        }

        $validated = $request->validate([
            'content'  => ['required', 'string', 'max:255'],
            'priority' => ['required', 'in:low,medium,high'],
        ]);

        OtaAnnouncement::create($validated);

        return back()->with('success', 'Announcement saved locally.');
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
