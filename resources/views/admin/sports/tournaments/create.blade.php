@extends('layouts.admin')

@section('title', 'Create Tournament')

@section('content')
<div class="space-y-6">
    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
        <a href="{{ route('admin.sports.tournaments.index') }}" class="hover:text-gray-700 dark:hover:text-gray-200 transition-colors">Tournaments</a>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
        <span class="text-gray-900 dark:text-gray-100 font-medium">Create New</span>
    </div>

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Create Tournament</h1>
    </div>

    <form method="POST" action="{{ route('admin.sports.tournaments.store') }}" enctype="multipart/form-data">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Main Column (2/3) --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Basic Info --}}
                <div class="admin-card">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Basic Information</h2>
                    </div>
                    <div class="px-6 py-4 space-y-4">
                        {{-- Name --}}
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Tournament Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="name" name="name" value="{{ old('name') }}" required
                                   class="form-input w-full @error('name') border-red-500 @enderror"
                                   placeholder="e.g. Premier League">
                            @error('name')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Sport --}}
                        <div>
                            <label for="sport_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Sport <span class="text-red-500">*</span>
                            </label>
                            <select id="sport_id" name="sport_id" required
                                    class="form-select w-full @error('sport_id') border-red-500 @enderror">
                                <option value="">Select a sport</option>
                                @foreach($sports as $sport)
                                    <option value="{{ $sport->id }}" @selected(old('sport_id') == $sport->id)>
                                        {{ $sport->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('sport_id')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Season --}}
                        <div>
                            <label for="season" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Season</label>
                            <input type="text" id="season" name="season" value="{{ old('season') }}"
                                   class="form-input w-full @error('season') border-red-500 @enderror"
                                   placeholder="e.g. 2025-26">
                            @error('season')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Country --}}
                        <div>
                            <label for="country" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Country</label>
                            <input type="text" id="country" name="country" value="{{ old('country') }}"
                                   class="form-input w-full @error('country') border-red-500 @enderror"
                                   placeholder="e.g. England">
                            @error('country')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Schedule --}}
                <div class="admin-card">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Schedule</h2>
                    </div>
                    <div class="px-6 py-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {{-- Start Date --}}
                        <div>
                            <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Start Date</label>
                            <input type="date" id="start_date" name="start_date" value="{{ old('start_date') }}"
                                   class="form-input w-full @error('start_date') border-red-500 @enderror">
                            @error('start_date')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- End Date --}}
                        <div>
                            <label for="end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">End Date</label>
                            <input type="date" id="end_date" name="end_date" value="{{ old('end_date') }}"
                                   class="form-input w-full @error('end_date') border-red-500 @enderror">
                            @error('end_date')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sidebar (1/3) --}}
            <div class="space-y-6">
                {{-- Logo --}}
                <div class="admin-card">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Logo</h2>
                    </div>
                    <div class="px-6 py-4">
                        <div class="flex items-center justify-center w-full">
                            <label for="logo" class="flex flex-col items-center justify-center w-full h-40 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer hover:border-gray-400 dark:hover:border-gray-500 transition-colors bg-gray-50 dark:bg-gray-800">
                                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                    <svg class="w-8 h-8 mb-3 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Click to upload logo</p>
                                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">PNG, JPG, SVG</p>
                                </div>
                                <input type="file" id="logo" name="logo" accept="image/*" class="hidden">
                            </label>
                        </div>
                        @error('logo')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Settings --}}
                <div class="admin-card">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Settings</h2>
                    </div>
                    <div class="px-6 py-4 space-y-4">
                        {{-- Featured --}}
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="hidden" name="is_featured" value="0">
                            <input type="checkbox" name="is_featured" value="1"
                                   class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500 dark:bg-gray-700"
                                   @checked(old('is_featured'))>
                            <div>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Featured</span>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Display this tournament in featured sections</p>
                            </div>
                        </label>

                        {{-- Active --}}
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1"
                                   class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500 dark:bg-gray-700"
                                   @checked(old('is_active', true))>
                            <div>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Active</span>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Tournament is visible on the site</p>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="admin-card">
                    <div class="px-6 py-4 space-y-3">
                        <button type="submit" class="btn-primary w-full justify-center inline-flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Create Tournament
                        </button>
                        <a href="{{ route('admin.sports.tournaments.index') }}" class="btn-secondary w-full justify-center inline-flex items-center gap-2">
                            Cancel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
