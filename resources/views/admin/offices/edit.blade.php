@extends('layouts.app')

@section('title', 'Edit Office')

@section('content')
<div class="max-w-lg">
    <h1 class="text-2xl font-bold text-slate-800 mb-6">Edit Office: {{ $office->name }}</h1>
    <form method="POST" action="{{ route('admin.offices.update', $office) }}" class="space-y-4">
        @csrf
        @method('PUT')
        <div>
            <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Name</label>
            <input type="text" name="name" id="name" value="{{ old('name', $office->name) }}" required
                class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
            @error('name')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="slug" class="block text-sm font-medium text-slate-700 mb-1">Slug</label>
            <input type="text" name="slug" id="slug" value="{{ old('slug', $office->slug) }}" required
                class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
            @error('slug')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="description" class="block text-sm font-medium text-slate-700 mb-1">Description (optional)</label>
            <textarea name="description" id="description" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">{{ old('description', $office->description) }}</textarea>
        </div>
        <div>
            <label for="location" class="block text-sm font-medium text-slate-700 mb-1">Location (optional)</label>
            <input type="text" name="location" id="location" value="{{ old('location', $office->location) }}"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="max_daily_queue" class="block text-sm font-medium text-slate-700 mb-1">Max daily queue</label>
                <input type="number" name="max_daily_queue" id="max_daily_queue" value="{{ old('max_daily_queue', $office->max_daily_queue) }}" min="1" max="500"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
            </div>
            <div>
                <label for="serving_time_minutes" class="block text-sm font-medium text-slate-700 mb-1">Serving time (min)</label>
                <input type="number" name="serving_time_minutes" id="serving_time_minutes" value="{{ old('serving_time_minutes', $office->serving_time_minutes) }}" min="1" max="120"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
            </div>
        </div>
        <div class="flex items-center">
            <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $office->is_active) ? 'checked' : '' }} class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
            <label for="is_active" class="ml-2 text-sm text-slate-700">Active</label>
        </div>
        <div class="flex gap-2 pt-2">
            <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 text-white font-medium hover:bg-emerald-700">Update Office</button>
            <a href="{{ route('admin.offices') }}" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50">Cancel</a>
        </div>
    </form>
</div>
@endsection
