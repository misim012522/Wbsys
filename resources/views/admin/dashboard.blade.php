@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
<div class="flex items-center justify-between mb-8">
    <h1 class="text-2xl font-bold text-slate-800">Admin Dashboard</h1>
    <div class="flex gap-2 flex-wrap">
        <a href="{{ route('admin.qr') }}" class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">QR codes</a>
        <a href="{{ route('admin.users.pending') }}" class="px-4 py-2 rounded-lg bg-slate-800 text-white text-sm font-medium hover:bg-slate-900">Pending accounts</a>
        <a href="{{ route('admin.users.index') }}" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm font-medium">Staff accounts</a>
        <a href="{{ route('admin.users.archived') }}" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm font-medium">Archived</a>
        <a href="{{ route('admin.reports') }}" class="px-4 py-2 rounded-lg bg-slate-200 text-slate-700 hover:bg-slate-300 text-sm font-medium">Reports</a>
        <a href="{{ route('admin.customization.index') }}" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm font-medium">Customization</a>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
    <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
        <p class="text-sm text-slate-500 mb-1">Today's active queue</p>
        <p class="text-3xl font-bold text-emerald-600">{{ $todayQueues }}</p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
        <p class="text-sm text-slate-500 mb-1">Today's appointments</p>
        <p class="text-3xl font-bold text-blue-600">{{ $todayAppointments }}</p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
        <p class="text-sm text-slate-500 mb-1">Completed today</p>
        <p class="text-3xl font-bold text-slate-700">{{ $completedToday }}</p>
    </div>
</div>

<h2 class="text-lg font-semibold text-slate-800 mb-4">Offices</h2>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    @foreach($offices as $office)
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="flex justify-between items-start mb-2">
                <h3 class="font-semibold text-slate-800">{{ $office->name }}</h3>
                <a href="{{ route('admin.offices.edit', $office) }}" class="text-sm text-slate-500 hover:underline">Edit</a>
            </div>
            <p class="text-sm text-slate-500 mb-3">Queue: {{ $office->queue_entries_count ?? 0 }} · Appointments: {{ $office->appointments_count ?? 0 }}</p>
            <div class="flex gap-2">
                <a href="{{ route('admin.serve', $office) }}" class="text-sm px-3 py-1.5 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700">Serve</a>
            </div>
        </div>
    @endforeach
</div>
<div class="mt-4">
    <a href="{{ route('admin.offices.create') }}" class="inline-flex items-center px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">Add Office</a>
</div>
@endsection
