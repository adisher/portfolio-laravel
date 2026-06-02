@extends('layouts.admin')

@section('title', 'My Profile - Admin Panel')

@section('content')
<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">My Profile</h1>
            <p class="text-gray-600 dark:text-gray-400">Manage your account settings and preferences</p>
        </div>
        <a href="{{ route('admin.profile.edit') }}" class="btn-primary">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                </path>
            </svg>
            Edit Profile
        </a>
    </div>
</div>

<!-- Profile Overview -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Profile Card -->
    <div class="admin-card p-6">
        <div class="text-center">
            <div class="relative inline-block">
                @if($user->profile_picture)
                <img src="{{ $user->profile_picture_url }}" alt="{{ $user->name }}"
                    class="w-24 h-24 rounded-full object-cover mx-auto mb-4">
                @else
                <div
                    class="w-24 h-24 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center mx-auto mb-4">
                    <span class="text-2xl font-bold text-gray-600 dark:text-gray-300">{{ $user->initials }}</span>
                </div>
                @endif
            </div>

            <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ $user->name }}</h2>
            @if($user->job_title)
            <p class="text-gray-600 dark:text-gray-400">{{ $user->job_title }}</p>
            @endif

            @if($user->location)
            <div class="flex items-center justify-center mt-2 text-sm text-gray-500 dark:text-gray-400">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                {{ $user->location }}
            </div>
            @endif

            @if($user->bio)
            <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">{{ $user->bio }}</p>
            @endif
        </div>
    </div>

    <!-- Profile Details -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Contact Information -->
        <div class="admin-card p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Contact Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Email</label>
                    <p class="text-gray-900 dark:text-white">{{ $user->email }}</p>
                </div>

                @if($user->phone)
                <div>
                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Phone</label>
                    <p class="text-gray-900 dark:text-white">{{ $user->phone }}</p>
                </div>
                @endif

                @if($user->website)
                <div>
                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Website</label>
                    <a href="{{ $user->website }}" target="_blank"
                        class="text-blue-600 dark:text-blue-400 hover:underline">{{ $user->website }}</a>
                </div>
                @endif

                <div>
                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Member Since</label>
                    <p class="text-gray-900 dark:text-white">{{ $user->created_at->format('M j, Y') }}</p>
                </div>
            </div>
        </div>

        <!-- Account Statistics -->
        <div class="admin-card p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Account Statistics</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="text-center">
                    <div class="text-2xl font-bold text-blue-600">{{ \App\Models\Project::count() }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Projects</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-600">{{ \App\Models\BlogPost::count() }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Blog Posts</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-purple-600">{{ \App\Models\Media::count() }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Media Files</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-orange-600">
                        @if($user->last_login_at)
                        {{ $user->last_login_at->diffForHumans() }}
                        @else
                        Never
                        @endif
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Last Login</div>
                </div>
            </div>
        </div>

        <!-- Notification Preferences -->
        <div class="admin-card p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Notification Preferences</h3>
                <button onclick="openNotificationModal()" class="btn-secondary text-sm">
                    Edit Preferences
                </button>
            </div>
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-gray-900 dark:text-white">Contact form submissions</span>
                    <span
                        class="text-sm {{ $user->getNotificationPreference('email_contact_form') ? 'text-green-600' : 'text-gray-500' }}">
                        {{ $user->getNotificationPreference('email_contact_form') ? 'Enabled' : 'Disabled' }}
                    </span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-900 dark:text-white">New comments</span>
                    <span
                        class="text-sm {{ $user->getNotificationPreference('email_new_comments') ? 'text-green-600' : 'text-gray-500' }}">
                        {{ $user->getNotificationPreference('email_new_comments') ? 'Enabled' : 'Disabled' }}
                    </span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-900 dark:text-white">System updates</span>
                    <span
                        class="text-sm {{ $user->getNotificationPreference('email_system_updates') ? 'text-green-600' : 'text-gray-500' }}">
                        {{ $user->getNotificationPreference('email_system_updates') ? 'Enabled' : 'Disabled' }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="admin-card p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Quick Actions</h3>
            <div class="flex flex-wrap gap-3">
                <button onclick="openPasswordModal()" class="btn-secondary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z">
                        </path>
                    </svg>
                    Change Password
                </button>

                <a href="{{ route('admin.media.index') }}" class="btn-secondary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                        </path>
                    </svg>
                    Manage Media
                </a>

                <a href="{{ route('admin.settings.index') }}" class="btn-secondary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                        </path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    Site Settings
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Password Change Modal -->
<div id="password-modal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closePasswordModal()"></div>

        <div
            class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form action="{{ route('admin.profile.password') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Change Password</h3>

                    <div class="space-y-4">
                        <div>
                            <label for="current_password"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Current
                                Password</label>
                            <input type="password" id="current_password" name="current_password" required
                                class="w-full form-input rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>

                        <div>
                            <label for="password"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">New
                                Password</label>
                            <input type="password" id="password" name="password" required
                                class="w-full form-input rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>

                        <div>
                            <label for="password_confirmation"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Confirm New
                                Password</label>
                            <input type="password" id="password_confirmation" name="password_confirmation" required
                                class="w-full form-input rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="btn-primary w-full sm:w-auto sm:ml-3">
                        Update Password
                    </button>
                    <button type="button" onclick="closePasswordModal()"
                        class="btn-secondary w-full sm:w-auto mt-3 sm:mt-0">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Notification Preferences Modal -->
<div id="notification-modal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeNotificationModal()">
        </div>

        <div
            class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form action="{{ route('admin.profile.notifications') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Notification Preferences</h3>

                    <div class="space-y-4">
                        <div class="flex items-center">
                            <input type="checkbox" id="email_contact_form" name="email_contact_form" value="1" {{
                                $user->getNotificationPreference('email_contact_form') ? 'checked' : '' }}
                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring
                            focus:ring-blue-200 focus:ring-opacity-50">
                            <label for="email_contact_form" class="ml-3 text-sm text-gray-700 dark:text-gray-300">
                                Email notifications for contact form submissions
                            </label>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" id="email_new_comments" name="email_new_comments" value="1" {{
                                $user->getNotificationPreference('email_new_comments') ? 'checked' : '' }}
                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring
                            focus:ring-blue-200 focus:ring-opacity-50">
                            <label for="email_new_comments" class="ml-3 text-sm text-gray-700 dark:text-gray-300">
                                Email notifications for new comments
                            </label>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" id="email_system_updates" name="email_system_updates" value="1" {{
                                $user->getNotificationPreference('email_system_updates') ? 'checked' : '' }}
                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring
                            focus:ring-blue-200 focus:ring-opacity-50">
                            <label for="email_system_updates" class="ml-3 text-sm text-gray-700 dark:text-gray-300">
                                Email notifications for system updates
                            </label>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="btn-primary w-full sm:w-auto sm:ml-3">
                        Save Preferences
                    </button>
                    <button type="button" onclick="closeNotificationModal()"
                        class="btn-secondary w-full sm:w-auto mt-3 sm:mt-0">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function openPasswordModal() {
    document.getElementById('password-modal').classList.remove('hidden');
}

function closePasswordModal() {
    document.getElementById('password-modal').classList.add('hidden');
}

function openNotificationModal() {
    document.getElementById('notification-modal').classList.remove('hidden');
}

function closeNotificationModal() {
    document.getElementById('notification-modal').classList.add('hidden');
}
</script>
@endpush