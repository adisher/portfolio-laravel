@extends('layouts.admin')

@section('title', 'Edit Match - Admin Panel')

@section('content')
<div class="mb-6">
    <div class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400 mb-2">
        <a href="{{ route('admin.sports.matches.index') }}" class="hover:text-gray-700 dark:hover:text-gray-300">Matches</a>
        <span>›</span>
        <span>Edit</span>
    </div>
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Edit Match</h1>
</div>

<form action="{{ route('admin.sports.matches.update', $match) }}" method="POST">
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
                        <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Match Title *
                        </label>
                        <input type="text" id="title" name="title" required value="{{ old('title', $match->title) }}"
                            placeholder="e.g., India vs Australia - 3rd T20I"
                            class="form-input w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('title') border-red-500 @enderror">
                        @error('title')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="sport_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Sport *
                        </label>
                        <select id="sport_id" name="sport_id" required
                            class="form-select w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('sport_id') border-red-500 @enderror">
                            <option value="">Select Sport</option>
                            @foreach($sports as $sport)
                            <option value="{{ $sport->id }}" {{ old('sport_id', $match->sport_id) == $sport->id ? 'selected' : '' }}>
                                {{ $sport->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('sport_id')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="tournament_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Tournament
                        </label>
                        <select id="tournament_id" name="tournament_id"
                            class="form-select w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('tournament_id') border-red-500 @enderror">
                            <option value="">No Tournament</option>
                            @foreach($tournaments as $tournament)
                            <option value="{{ $tournament->id }}" {{ old('tournament_id', $match->tournament_id) == $tournament->id ? 'selected' : '' }}>
                                {{ $tournament->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('tournament_id')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="home_team_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Home Team *
                        </label>
                        <select id="home_team_id" name="home_team_id" required
                            class="form-select w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('home_team_id') border-red-500 @enderror">
                            <option value="">Select Home Team</option>
                            @foreach($teams as $team)
                            <option value="{{ $team->id }}" {{ old('home_team_id', $match->home_team_id) == $team->id ? 'selected' : '' }}>
                                {{ $team->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('home_team_id')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="away_team_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Away Team *
                        </label>
                        <select id="away_team_id" name="away_team_id" required
                            class="form-select w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('away_team_id') border-red-500 @enderror">
                            <option value="">Select Away Team</option>
                            @foreach($teams as $team)
                            <option value="{{ $team->id }}" {{ old('away_team_id', $match->away_team_id) == $team->id ? 'selected' : '' }}>
                                {{ $team->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('away_team_id')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Must be different from home team</p>
                    </div>
                </div>
            </div>

            <!-- Match Details -->
            <div class="admin-card p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Match Details</h2>

                <div class="space-y-4">
                    <div>
                        <label for="venue" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Venue
                        </label>
                        <input type="text" id="venue" name="venue" value="{{ old('venue', $match->venue) }}"
                            placeholder="e.g., Melbourne Cricket Ground"
                            class="form-input w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('venue') border-red-500 @enderror">
                        @error('venue')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="city" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                City
                            </label>
                            <input type="text" id="city" name="city" value="{{ old('city', $match->city) }}"
                                placeholder="e.g., Melbourne"
                                class="form-input w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('city') border-red-500 @enderror">
                            @error('city')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="country" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Country
                            </label>
                            <input type="text" id="country" name="country" value="{{ old('country', $match->country) }}"
                                placeholder="e.g., Australia"
                                class="form-input w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('country') border-red-500 @enderror">
                            @error('country')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label for="match_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Match Type
                        </label>
                        <input type="text" id="match_type" name="match_type" value="{{ old('match_type', $match->match_type) }}"
                            placeholder="e.g., T20, ODI, League, Friendly"
                            class="form-input w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('match_type') border-red-500 @enderror">
                        @error('match_type')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="result_summary" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Result Summary
                        </label>
                        <input type="text" id="result_summary" name="result_summary" value="{{ old('result_summary', $match->result_summary) }}"
                            placeholder="e.g., India won by 5 wickets"
                            class="form-input w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('result_summary') border-red-500 @enderror">
                        @error('result_summary')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Brief summary of the match result (for completed matches)</p>
                    </div>

                    <div>
                        <label for="meta_description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Meta Description
                        </label>
                        <textarea id="meta_description" name="meta_description" rows="3"
                            class="form-input w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('meta_description') border-red-500 @enderror"
                            placeholder="Brief description for SEO purposes...">{{ old('meta_description', $match->meta_description) }}</textarea>
                        @error('meta_description')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Schedule -->
            <div class="admin-card p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Schedule</h2>

                <div class="space-y-4">
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Status *
                        </label>
                        <select id="status" name="status" required
                            class="form-select w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('status') border-red-500 @enderror">
                            <option value="scheduled" {{ old('status', $match->status) === 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                            <option value="live" {{ old('status', $match->status) === 'live' ? 'selected' : '' }}>Live</option>
                            <option value="completed" {{ old('status', $match->status) === 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="postponed" {{ old('status', $match->status) === 'postponed' ? 'selected' : '' }}>Postponed</option>
                            <option value="cancelled" {{ old('status', $match->status) === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            <option value="abandoned" {{ old('status', $match->status) === 'abandoned' ? 'selected' : '' }}>Abandoned</option>
                        </select>
                        @error('status')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="scheduled_at" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Scheduled At
                        </label>
                        <input type="datetime-local" id="scheduled_at" name="scheduled_at"
                            value="{{ old('scheduled_at', $match->scheduled_at ? $match->scheduled_at->format('Y-m-d\TH:i') : '') }}"
                            class="form-input w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('scheduled_at') border-red-500 @enderror">
                        @error('scheduled_at')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Settings -->
            <div class="admin-card p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Settings</h2>

                <div class="space-y-4">
                    <label class="flex items-center">
                        <input type="hidden" name="is_featured" value="0">
                        <input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $match->is_featured) ? 'checked' : '' }}
                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:border-gray-600 dark:bg-gray-700">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Featured Match</span>
                    </label>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Featured matches appear prominently on the sports homepage</p>
                </div>
            </div>

            <!-- Actions -->
            <div class="admin-card p-6">
                <div class="flex space-x-3">
                    <button type="submit" class="flex-1 btn-primary">
                        Update Match
                    </button>
                    <a href="{{ route('admin.sports.matches.index') }}" class="flex-1 btn-secondary text-center">
                        Cancel
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
