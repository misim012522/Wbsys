@extends('layouts.app')

@section('title', 'Create account')

@section('content')
<div class="max-w-lg">
    <div class="mb-6">
        <a href="{{ route('admin.users.index') }}" class="text-sm text-slate-600 hover:text-slate-800">← Staff accounts</a>
    </div>
    <h1 class="text-2xl font-bold text-slate-800 mb-6">Create account</h1>
    <p class="text-slate-600 mb-6">Create a staff account so they can log in and serve the queue for their office.</p>

    <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-4">
        @csrf
        <div>
            <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Name <span class="text-red-500">*</span></label>
            <input type="text" name="name" id="name" value="{{ old('name') }}" required
                class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                placeholder="e.g. Maria Santos">
            @error('name')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="username" class="block text-sm font-medium text-slate-700 mb-1">Username <span class="text-red-500">*</span></label>
            <input type="text" name="username" id="username" value="{{ old('username') }}" required
                class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                placeholder="e.g. maria.santos">
            @error('username')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email <span class="text-red-500">*</span></label>
            <input type="email" name="email" id="email" value="{{ old('email') }}" required
                class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                placeholder="maria@school.edu">
            @error('email')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="phone" class="block text-sm font-medium text-slate-700 mb-1">Contact number</label>
            <input type="text" name="phone" id="phone" value="{{ old('phone') }}"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                placeholder="09XX XXX XXXX">
            @error('phone')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Password <span class="text-red-500">*</span></label>
            <input type="password" name="password" id="password" required
                class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
            @error('password')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-1">Confirm password <span class="text-red-500">*</span></label>
            <input type="password" name="password_confirmation" id="password_confirmation" required
                class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
        </div>
        <div>
            <label for="office_id" class="block text-sm font-medium text-slate-700 mb-1">Office <span class="text-red-500">*</span></label>
            <select name="office_id" id="office_id" required
                class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                <option value="">— Select office —</option>
                @foreach($offices as $office)
                    <option value="{{ $office->id }}" {{ old('office_id') == $office->id ? 'selected' : '' }}>{{ $office->name }}</option>
                @endforeach
            </select>
            @error('office_id')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>
        <div class="flex gap-2 pt-2">
            <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 text-white font-medium hover:bg-emerald-700">Create account</button>
            <a href="{{ route('admin.users.index') }}" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-white/50/30">Cancel</a>
        </div>
    </form>
</div>
@endsection
