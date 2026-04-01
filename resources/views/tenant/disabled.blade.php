@extends('layouts.public')

@section('title', 'Workspace Disabled')

@section('public_full_width', true)

@section('content')
<div class="min-h-screen bg-[radial-gradient(circle_at_top,_rgba(15,23,42,0.06),_transparent_35%),linear-gradient(180deg,_#f8fafc_0%,_#eef2ff_100%)] px-4 py-10">
    <div class="mx-auto max-w-4xl">
        <div class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-2xl shadow-slate-200/70">
            <div class="bg-[linear-gradient(135deg,_#0f172a_0%,_#1e293b_50%,_#334155_100%)] px-8 py-10 text-white">
                <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-300">Tenant Workspace</p>
                <h1 class="mt-4 text-4xl font-bold tracking-tight">{{ $tenant->name }}</h1>
                <p class="mt-4 max-w-2xl text-sm leading-7 text-slate-200">
                    This workspace is currently disabled by the central administrator. Tenant staff and tenant admins cannot sign in or continue using protected workspace pages until access is restored.
                </p>
            </div>

            <div class="grid gap-6 px-8 py-8 lg:grid-cols-[1.3fr,0.9fr]">
                <div class="space-y-5">
                    <div class="rounded-[1.5rem] border border-amber-200 bg-amber-50 px-5 py-4">
                        <p class="text-sm font-semibold text-amber-900">Workspace status: Disabled</p>
                        <p class="mt-2 text-sm leading-6 text-amber-800">
                            If you expected this workspace to be available, contact your system administrator or the central operations team to reactivate the tenant.
                        </p>
                    </div>

                    <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 px-5 py-4">
                        <p class="text-sm font-semibold text-slate-900">What this means</p>
                        <ul class="mt-3 space-y-2 text-sm text-slate-600">
                            <li>Tenant logins are blocked while the workspace remains disabled.</li>
                            <li>Existing authenticated tenant sessions are signed out from protected pages.</li>
                            <li>Public central management remains available to authorized central administrators.</li>
                        </ul>
                    </div>
                </div>

                <div class="rounded-[1.5rem] border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-400">Workspace details</p>
                    <dl class="mt-4 space-y-4 text-sm">
                        <div>
                            <dt class="text-slate-500">Workspace URL</dt>
                            <dd class="mt-1 break-all font-medium text-slate-900">{{ $workspaceUrl }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500">Tenant contact email</dt>
                            <dd class="mt-1 font-medium text-slate-900">{{ $contactEmail ?: 'Not available' }}</dd>
                        </div>
                    </dl>

                    <div class="mt-6 flex flex-col gap-3">
                        <a href="{{ $centralHomeUrl }}" class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white hover:bg-slate-800">
                            Go to central app
                        </a>
                        <a href="mailto:{{ $contactEmail }}" class="inline-flex items-center justify-center rounded-xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 {{ $contactEmail ? '' : 'pointer-events-none opacity-50' }}">
                            Contact tenant administrator
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
