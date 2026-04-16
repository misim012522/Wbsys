@extends('layouts.app')

@section('title', 'Home')

@section('content')
<section class="panel overflow-hidden">
    <div class="panel-section bg-[linear-gradient(180deg,_#ffffff_0%,_#f8fafc_100%)] text-center sm:px-10 sm:py-16">
        <p class="info-kicker">Queue management platform</p>
        <h1 class="mx-auto mt-3 max-w-3xl text-4xl font-bold tracking-tight text-slate-900 sm:text-5xl">QueueLess</h1>
        <p class="mx-auto mt-4 max-w-3xl text-base leading-7 text-slate-600 sm:text-lg">
            Central management and tenant workspaces for queues and appointments.
        </p>
        <p class="mx-auto mt-6 max-w-2xl text-sm leading-7 text-slate-500 sm:text-[15px]">
            The sysadmin manages tenant registration from the central app. Tenant admins and staff run daily operations,
            while approved tenant users can sign in within their workspace to request queue numbers and appointments.
        </p>
        @guest
            <div class="mx-auto mt-8 max-w-2xl rounded-[1.5rem] border border-slate-200 bg-slate-50 px-6 py-5 text-sm leading-7 text-slate-500">
                <p>The central app is for the sysadmin account only.</p>
                <p class="mt-2">If you already belong to a tenant, open that tenant's link or subdomain to create your own account and wait for admin approval.</p>
            </div>
        @endguest
    </div>
</section>
@endsection
