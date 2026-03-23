@extends('layouts.app')

@section('title', 'Home')

@section('content')
<div class="text-center py-16">
    <h1 class="text-4xl font-bold text-slate-800 mb-2">QueueLess</h1>
    <p class="text-lg text-slate-600 mb-8">Central management and tenant workspaces for queues and appointments</p>
    <p class="text-slate-500 max-w-2xl mx-auto mb-10">
        The sysadmin manages tenant registration from the central app. Tenant admins and staff manage operations,
        while approved tenant users can sign in to request queue numbers and appointments inside their tenant workspace.
    </p>
    @guest
        <p class="mt-4 text-sm text-slate-500">The central app is for the sysadmin account only.</p>
        <p class="mt-2 text-sm text-slate-500">If you already belong to a tenant, open that tenant's link or subdomain to create your own account and wait for admin approval.</p>
    @endguest
</div>
@endsection
