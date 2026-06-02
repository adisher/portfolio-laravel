@extends('layouts.admin')

@section('title', 'Edit Team - Admin Panel')

@section('content')
<div class="mb-6">
    <div class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400 mb-2">
        <a href="{{ route('admin.sports.teams.index') }}" class="hover:text-gray-700 dark:hover:text-gray-300">Teams</a>
        <span>›</span>
        <span>{{ $team->name }}</span>
        <span>›</span>
        <span>Edit</span>
    </div>
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Edit Team</h1>
</div>

<form action="{{ route('admin.sports.teams.update', $team) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Basic Information -->
            <div class="admin-card p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Basic Information</h2>

                <div class="space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Team Name *
                        </label>
                        <input type="text" id="name" name="name" required value="{{ old('name', $team->name) }}"
                            class="form-input w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('name') border-red-500 @enderror">
                        @error('name')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="short_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Short Name
                        </label>
                        <input type="text" id="short_name" name="short_name" value="{{ old('short_name', $team->short_name) }}"
                            class="form-input w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('short_name') border-red-500 @enderror">
                        @error('short_name')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="abbreviation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Abbreviation *
                        </label>
                        <input type="text" id="abbreviation" name="abbreviation" required maxlength="5" value="{{ old('abbreviation', $team->abbreviation) }}"
                            placeholder="e.g., MUN, BAR, LAL"
                            class="form-input w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('abbreviation') border-red-500 @enderror">
                        @error('abbreviation')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Maximum 5 characters</p>
                    </div>

                    <div>
                        <label for="sport_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Sport *
                        </label>
                        <select id="sport_id" name="sport_id" required
                            class="form-select w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('sport_id') border-red-500 @enderror">
                            <option value="">Select Sport</option>
                            @foreach($sports as $sport)
                            <option value="{{ $sport->id }}" {{ old('sport_id', $team->sport_id) == $sport->id ? 'selected' : '' }}>
                                {{ $sport->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('sport_id')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Location -->
            <div class="admin-card p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Location</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="country_code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Country Code
                        </label>
                        <input type="text" id="country_code" name="country_code" maxlength="2" value="{{ old('country_code', $team->country_code) }}"
                            placeholder="e.g., US, GB, ES"
                            class="form-input w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('country_code') border-red-500 @enderror">
                        @error('country_code')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">2-letter ISO country code</p>
                    </div>

                    <div>
                        <label for="primary_color" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Primary Color
                        </label>
                        <input type="color" id="primary_color" name="primary_color" value="{{ old('primary_color', $team->primary_color ?? '#3B82F6') }}"
                            class="w-full h-10 rounded-md border-gray-300 dark:border-gray-600 cursor-pointer @error('primary_color') border-red-500 @enderror">
                        @error('primary_color')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Logo -->
            <div class="admin-card p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Logo</h2>

                @if($team->logo)
                <div class="mb-4">
                    <img src="{{ Storage::url($team->logo) }}" alt="{{ $team->name }}"
                        class="w-24 h-24 rounded-full object-cover mx-auto">
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2 text-center">Current logo</p>
                </div>
                @endif

                <div>
                    <label for="logo" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ $team->logo ? 'Replace Logo' : 'Team Logo' }}
                    </label>
                    <input type="file" id="logo" name="logo" accept="image/*"
                        class="w-full text-sm text-gray-500 dark:text-gray-400
                            file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0
                            file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700
                            dark:file:bg-blue-900 dark:file:text-blue-300
                            hover:file:bg-blue-100 dark:hover:file:bg-blue-800
                            @error('logo') border-red-500 @enderror">
                    @error('logo')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Leave empty to keep current logo</p>
                </div>
            </div>

            <!-- Settings -->
            <div class="admin-card p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Settings</h2>

                <div class="space-y-2">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $team->is_active) ? 'checked' : '' }}
                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Active</span>
                    </label>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Inactive teams will not appear in public listings</p>
                </div>
            </div>

            <!-- Actions -->
            <div class="admin-card p-6">
                <div class="flex space-x-3">
                    <button type="submit" class="flex-1 btn-primary">
                        Update Team
                    </button>
                    <a href="{{ route('admin.sports.teams.index') }}" class="flex-1 btn-secondary text-center">
                        Cancel
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
