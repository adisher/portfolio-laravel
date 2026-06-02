@extends('layouts.admin')

@section('title', 'Add Testimonial - Admin Panel')

@section('content')
<div class="mb-6">
    <div class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400 mb-2">
        <a href="{{ route('admin.testimonials.index') }}" class="hover:text-gray-700">Testimonials</a>
        <span>></span>
        <span>Add New</span>
    </div>
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Add Testimonial</h1>
</div>

<form action="{{ route('admin.testimonials.store') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Client Information -->
            <div class="admin-card p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Client Information</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="client_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Client Name *
                        </label>
                        <input type="text" id="client_name" name="client_name" required value="{{ old('client_name') }}"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('client_name') border-red-500 @enderror">
                        @error('client_name')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="client_position" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Position/Title
                        </label>
                        <input type="text" id="client_position" name="client_position" value="{{ old('client_position') }}"
                            placeholder="e.g., CEO, CTO, Project Manager"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>

                    <div>
                        <label for="client_company" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Company
                        </label>
                        <input type="text" id="client_company" name="client_company" value="{{ old('client_company') }}"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>

                    <div>
                        <label for="rating" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Rating *
                        </label>
                        <select id="rating" name="rating" required
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            @for($i = 5; $i >= 1; $i--)
                            <option value="{{ $i }}" {{ old('rating', 5) == $i ? 'selected' : '' }}>
                                {{ $i }} Star{{ $i > 1 ? 's' : '' }}
                            </option>
                            @endfor
                        </select>
                    </div>

                    <div>
                        <label for="client_website" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Website URL
                        </label>
                        <input type="url" id="client_website" name="client_website" value="{{ old('client_website') }}"
                            placeholder="https://example.com"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>

                    <div>
                        <label for="client_linkedin" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            LinkedIn URL
                        </label>
                        <input type="url" id="client_linkedin" name="client_linkedin" value="{{ old('client_linkedin') }}"
                            placeholder="https://linkedin.com/in/username"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>
                </div>

                <div class="mt-4">
                    <label for="testimonial" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Testimonial Text *
                    </label>
                    <textarea id="testimonial" name="testimonial" rows="4" required
                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('testimonial') border-red-500 @enderror">{{ old('testimonial') }}</textarea>
                    @error('testimonial')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Your Role -->
            <div class="admin-card p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Your Role</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Describe your role on this client's project. Use &rarr; for role progression.</p>

                <div class="space-y-4">
                    <div>
                        <label for="client_role" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Role Title
                        </label>
                        <input type="text" id="client_role" name="client_role" value="{{ old('client_role') }}"
                            placeholder="e.g., Full-Stack Developer &rarr; Project Manager"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>

                    <div>
                        <label for="role_description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Role Description
                        </label>
                        <textarea id="role_description" name="role_description" rows="2"
                            placeholder="e.g., Led delivery across core platform systems and coordinated cross-functional execution."
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">{{ old('role_description') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Location Information -->
            <div class="admin-card p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Location (for Globe Display)</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Add location details to show this testimonial on the interactive globe map</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="country_code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Country Code (ISO 3166-1)
                        </label>
                        <input type="text" id="country_code" name="country_code" value="{{ old('country_code') }}"
                            placeholder="e.g., US, GB, DE, IN"
                            maxlength="2"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white uppercase">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">2-letter country code (e.g., US for United States)</p>
                    </div>

                    <div>
                        <label for="country_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Country Name
                        </label>
                        <input type="text" id="country_name" name="country_name" value="{{ old('country_name') }}"
                            placeholder="e.g., United States"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>

                    <div>
                        <label for="city" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            City
                        </label>
                        <input type="text" id="city" name="city" value="{{ old('city') }}"
                            placeholder="e.g., New York, London"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>

                    <div class="md:col-span-2">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="latitude" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Latitude
                                </label>
                                <input type="number" id="latitude" name="latitude" value="{{ old('latitude') }}"
                                    step="0.00000001" min="-90" max="90"
                                    placeholder="e.g., 40.7128"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            </div>
                            <div>
                                <label for="longitude" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Longitude
                                </label>
                                <input type="number" id="longitude" name="longitude" value="{{ old('longitude') }}"
                                    step="0.00000001" min="-180" max="180"
                                    placeholder="e.g., -74.0060"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Coordinates are required for globe marker placement. You can look up coordinates at <a href="https://www.latlong.net/" target="_blank" class="text-blue-500 hover:underline">latlong.net</a></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Client Image -->
            <div class="admin-card p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Client Photo</h2>

                <div>
                    <label for="client_image" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Profile Image (Optional)
                    </label>
                    <input type="file" id="client_image" name="client_image" accept="image/*"
                        class="w-full">
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Square image recommended. Max 2MB.</p>
                </div>
            </div>

            <!-- Settings -->
            <div class="admin-card p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Settings</h2>

                <div class="space-y-4">
                    <div>
                        <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Type *
                        </label>
                        <select id="type" name="type" required
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <option value="client" {{ old('type', 'client') === 'client' ? 'selected' : '' }}>Client</option>
                            <option value="colleague" {{ old('type') === 'colleague' ? 'selected' : '' }}>Colleague</option>
                            <option value="user" {{ old('type') === 'user' ? 'selected' : '' }}>Product User</option>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">For admin reference only — not shown on frontend</p>
                    </div>

                    <div>
                        <label for="sort_order" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Sort Order
                        </label>
                        <input type="number" id="sort_order" name="sort_order" value="{{ old('sort_order', 0) }}"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <p class="text-xs text-gray-500 mt-1">Lower numbers appear first</p>
                    </div>

                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="checkbox" name="is_featured" value="1" {{ old('is_featured') ? 'checked' : '' }}
                                class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Featured Testimonial</span>
                        </label>

                        <label class="flex items-center">
                            <input type="checkbox" name="is_published" value="1" {{ old('is_published', true) ? 'checked' : '' }}
                                class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Published</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="admin-card p-6">
                <div class="flex space-x-3">
                    <button type="submit" class="flex-1 btn-primary">
                        Create Testimonial
                    </button>
                    <a href="{{ route('admin.testimonials.index') }}" class="flex-1 btn-secondary text-center">
                        Cancel
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
