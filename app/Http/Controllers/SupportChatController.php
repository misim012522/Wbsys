<?php

namespace App\Http\Controllers;

use App\Models\SupportMessage;
use App\Models\SupportThread;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SupportChatController extends Controller
{
    public function tenantIndex(Request $request): View
    {
        $tenant = app()->bound('current_tenant') ? app('current_tenant') : $request->user()?->tenant;
        abort_unless($tenant, 404);

        if (! SupportThread::supportTablesExist()) {
            return view('support.tenant', [
                'tenant' => $tenant,
                'threads' => collect(),
                'activeThread' => null,
                'unreadCount' => 0,
                'supportReady' => false,
            ]);
        }

        $threads = SupportThread::query()
            ->where('tenant_id', $tenant->id)
            ->with('tenant')
            ->orderByDesc(DB::raw('COALESCE(last_message_at, created_at)'))
            ->get();

        $thread = $this->resolveTenantThread($request, $threads, $tenant);

        if ($thread) {
            $thread->load('messages');
            $thread->markReadForTenant();
        }

        return view('support.tenant', [
            'tenant' => $tenant,
            'threads' => $threads,
            'activeThread' => $thread,
            'unreadCount' => $threads->filter(fn (SupportThread $thread) => $thread->hasUnreadForTenant())->count(),
            'supportReady' => true,
        ]);
    }

    public function tenantSnapshot(Request $request)
    {
        $tenant = app()->bound('current_tenant') ? app('current_tenant') : $request->user()?->tenant;
        abort_unless($tenant, 404);

        if (! SupportThread::supportTablesExist()) {
            return response()->json([
                'thread_list_html' => view('support.partials.tenant-thread-list', [
                    'threads' => collect(),
                    'activeThread' => null,
                ])->render(),
                'conversation_html' => view('support.partials.tenant-conversation', [
                    'activeThread' => null,
                ])->render(),
                'unread_count' => 0,
                'support_ready' => false,
            ]);
        }

        $threads = SupportThread::query()
            ->where('tenant_id', $tenant->id)
            ->with('tenant')
            ->orderByDesc(DB::raw('COALESCE(last_message_at, created_at)'))
            ->get();

        $thread = $this->resolveTenantThread($request, $threads, $tenant);

        if ($thread) {
            $thread->load('messages');
        }

        return response()->json([
            'thread_list_html' => view('support.partials.tenant-thread-list', [
                'threads' => $threads,
                'activeThread' => $thread,
            ])->render(),
            'conversation_html' => view('support.partials.tenant-conversation', [
                'activeThread' => $thread,
            ])->render(),
            'unread_count' => $threads->filter(fn (SupportThread $thread) => $thread->hasUnreadForTenant())->count(),
        ]);
    }

    public function tenantStoreThread(Request $request): RedirectResponse
    {
        $tenant = app()->bound('current_tenant') ? app('current_tenant') : $request->user()?->tenant;
        abort_unless($tenant, 404);
        abort_unless(SupportThread::supportTablesExist(), 503, 'Support system is not ready yet.');

        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $thread = null;

        DB::connection('central')->transaction(function () use ($tenant, $request, $validated, &$thread): void {
            $thread = SupportThread::create([
                'tenant_id' => $tenant->id,
                'thread_type' => SupportThread::TYPE_SUPPORT,
                'subject' => trim($validated['subject']),
                'status' => SupportThread::STATUS_OPEN,
                'last_message_at' => now(),
                'tenant_last_read_at' => now(),
                'central_last_read_at' => null,
            ]);

            $this->createMessage(
                $thread,
                SupportMessage::SENDER_TENANT,
                $request->user()?->getKey(),
                $request->user()?->name ?? 'Tenant user',
                $request->user()?->roleLabel() ?? 'Tenant user',
                $validated['message']
            );
        });

        return redirect()
            ->route('support.tenant.index', ['thread' => $thread?->id])
            ->with('success', 'Your support thread has been created.');
    }

    public function tenantStoreMessage(Request $request, SupportThread $thread): RedirectResponse
    {
        $tenant = app()->bound('current_tenant') ? app('current_tenant') : $request->user()?->tenant;
        abort_unless($tenant && $thread->tenant_id === $tenant->id, 404);
        abort_unless(SupportThread::supportTablesExist(), 503, 'Support system is not ready yet.');

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        DB::connection('central')->transaction(function () use ($request, $thread, $validated): void {
            if ($thread->status === SupportThread::STATUS_RESOLVED) {
                $thread->forceFill(['status' => SupportThread::STATUS_IN_PROGRESS])->save();
            }

            $this->createMessage(
                $thread,
                SupportMessage::SENDER_TENANT,
                $request->user()?->getKey(),
                $request->user()?->name ?? 'Tenant user',
                $request->user()?->roleLabel() ?? 'Tenant user',
                $validated['message']
            );

            $thread->forceFill([
                'last_message_at' => now(),
                'tenant_last_read_at' => now(),
            ])->save();
        });

        return redirect()
            ->route('support.tenant.index', ['thread' => $thread->id])
            ->with('success', 'Your message has been sent to central support.');
    }

    public function centralIndex(Request $request): View
    {
        if (! SupportThread::supportTablesExist()) {
            return view('support.central', [
                'threads' => collect(),
                'activeThread' => null,
                'unreadCount' => 0,
                'tenants' => Tenant::query()->orderBy('name')->get(),
                'supportReady' => false,
            ]);
        }

        $threads = SupportThread::query()
            ->with('tenant')
            ->orderByDesc(DB::raw('COALESCE(last_message_at, created_at)'))
            ->get();

        $thread = $this->resolveCentralThread($request, $threads);

        if ($thread) {
            $thread->load('messages', 'tenant');
            $thread->markReadForCentral();
        }

        return view('support.central', [
            'threads' => $threads,
            'activeThread' => $thread,
            'unreadCount' => $threads->filter(fn (SupportThread $thread) => $thread->hasUnreadForCentral())->count(),
            'tenants' => Tenant::query()->orderBy('name')->get(),
            'supportReady' => true,
        ]);
    }

    public function centralSnapshot(Request $request)
    {
        if (! SupportThread::supportTablesExist()) {
            return response()->json([
                'thread_list_html' => view('support.partials.central-thread-list', [
                    'threads' => collect(),
                    'activeThread' => null,
                ])->render(),
                'conversation_html' => view('support.partials.central-conversation', [
                    'activeThread' => null,
                ])->render(),
                'unread_count' => 0,
                'support_ready' => false,
            ]);
        }

        $threads = SupportThread::query()
            ->with('tenant')
            ->orderByDesc(DB::raw('COALESCE(last_message_at, created_at)'))
            ->get();

        $thread = $this->resolveCentralThread($request, $threads);

        if ($thread) {
            $thread->load('messages', 'tenant');
        }

        return response()->json([
            'thread_list_html' => view('support.partials.central-thread-list', [
                'threads' => $threads,
                'activeThread' => $thread,
            ])->render(),
            'conversation_html' => view('support.partials.central-conversation', [
                'activeThread' => $thread,
            ])->render(),
            'unread_count' => $threads->filter(fn (SupportThread $thread) => $thread->hasUnreadForCentral())->count(),
        ]);
    }

    public function centralStoreAnnouncement(Request $request): RedirectResponse
    {
        abort_unless(SupportThread::supportTablesExist(), 503, 'Support system is not ready yet.');

        $validated = $request->validate([
            'tenant_id' => ['required', 'exists:tenants,id'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $thread = null;

        DB::connection('central')->transaction(function () use ($request, $validated, &$thread): void {
            $thread = SupportThread::create([
                'tenant_id' => (int) $validated['tenant_id'],
                'thread_type' => SupportThread::TYPE_ANNOUNCEMENT,
                'subject' => trim($validated['subject']),
                'status' => SupportThread::STATUS_OPEN,
                'last_message_at' => now(),
                'tenant_last_read_at' => null,
                'central_last_read_at' => now(),
            ]);

            $this->createMessage(
                $thread,
                SupportMessage::SENDER_CENTRAL,
                $request->user()?->getKey(),
                $request->user()?->name ?? 'Central admin',
                $request->user()?->roleLabel() ?? 'Central admin',
                $validated['message']
            );
        });

        return redirect()
            ->route('central.support.index', ['thread' => $thread?->id])
            ->with('success', 'Announcement has been sent to the tenant.');
    }

    public function centralStoreMessage(Request $request, SupportThread $thread): RedirectResponse
    {
        abort_unless(SupportThread::supportTablesExist(), 503, 'Support system is not ready yet.');

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        DB::connection('central')->transaction(function () use ($request, $thread, $validated): void {
            $this->createMessage(
                $thread,
                SupportMessage::SENDER_CENTRAL,
                $request->user()?->getKey(),
                $request->user()?->name ?? 'Central admin',
                $request->user()?->roleLabel() ?? 'Central admin',
                $validated['message']
            );

            $thread->forceFill([
                'status' => $thread->status === SupportThread::STATUS_OPEN ? SupportThread::STATUS_IN_PROGRESS : $thread->status,
                'last_message_at' => now(),
                'central_last_read_at' => now(),
            ])->save();
        });

        return redirect()
            ->route('support.central.index', ['thread' => $thread->id])
            ->with('success', 'Your reply has been sent to the tenant.');
    }

    public function centralUpdateStatus(Request $request, SupportThread $thread): RedirectResponse
    {
        abort_unless(SupportThread::supportTablesExist(), 503, 'Support system is not ready yet.');

        $validated = $request->validate([
            'status' => ['required', 'in:open,in_progress,resolved'],
        ]);

        $thread->forceFill(['status' => $validated['status']])->save();

        return redirect()
            ->route('support.central.index', ['thread' => $thread->id])
            ->with('success', 'Support thread status updated.');
    }

    private function createMessage(
        SupportThread $thread,
        string $senderType,
        ?int $senderId,
        string $senderName,
        ?string $senderRole,
        string $message
    ): SupportMessage {
        return $thread->messages()->create([
            'sender_type' => $senderType,
            'sender_id' => $senderId,
            'sender_name' => $senderName,
            'sender_role' => $senderRole,
            'message' => trim($message),
        ]);
    }

    private function resolveTenantThread(Request $request, $threads, Tenant $tenant): ?SupportThread
    {
        $threadId = $request->integer('thread');

        if ($threadId) {
            return $threads->firstWhere('id', $threadId);
        }

        return $threads->first();
    }

    private function resolveCentralThread(Request $request, $threads): ?SupportThread
    {
        $threadId = $request->integer('thread');

        if ($threadId) {
            return $threads->firstWhere('id', $threadId);
        }

        return $threads->first();
    }
}
