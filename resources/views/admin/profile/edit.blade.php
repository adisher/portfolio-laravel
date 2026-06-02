@extends('layouts.admin')

@section('title', 'Edit Profile - Admin Panel')

@section('content')
<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Edit Profile</h1>
            <p class="text-gray-600 dark:text-gray-400">Update your personal information and settings</p>
        </div>
        <a href="{{ route('admin.profile.show') }}" class="btn-secondary">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18">
                </path>
            </svg>
            Back to Profile
        </a>
    </div>
</div>

<form action="{{ route('admin.profile.update') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Profile Picture Section -->
        <div class="admin-card p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Profile Picture</h2>

            <div class="text-center">
                <div class="relative inline-block mb-4">
                    @if($user->profile_picture)
                    <img id="profile-preview" src="{{ $user->profile_picture_url }}" alt="{{ $user->name }}"
                        class="w-32 h-32 rounded-full object-cover mx-auto">
                    @else
                    <div id="profile-preview"
                        class="w-32 h-32 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center mx-auto">
                        <span class="text-3xl font-bold text-gray-600 dark:text-gray-300">{{ $user->initials }}</span>
                    </div>
                    @endif

                    @if($user->profile_picture)
                    <button type="button" onclick="removeProfilePicture()"
                        class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 hover:bg-red-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                    @endif
                </div>

                <div class="space-y-3">
                    <input type="file" id="profile_picture" name="profile_picture" accept="image/*" class="hidden"
                        onchange="previewImage(this)">
                    <button type="button" onclick="document.getElementById('profile_picture').click()"
                        class="btn-secondary w-full">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12">
                            </path>
                        </svg>
                        Upload New Picture
                    </button>

                    <button type="button" onclick="openMediaPicker(selectProfilePicture)" class="btn-secondary w-full">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                            </path>
                        </svg>
                        Choose from Media
                    </button>
                </div>

                @error('profile_picture')
                <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                @enderror

                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                    JPG, PNG, GIF up to 2MB. Recommended: 400x400px
                </p>
            </div>
        </div>

        <!-- Profile Information -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Basic Information -->
            <div class="admin-card p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Basic Information</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Full
                            Name *</label>
                        <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required
                            class="w-full form-input rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('name') border-red-500 @enderror">
                        @error('name')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email
                            Address *</label>
                        <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required
                            class="w-full form-input rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('email') border-red-500 @enderror">
                        @error('email')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="job_title"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Job Title</label>
                        <input type="text" id="job_title" name="job_title"
                            value="{{ old('job_title', $user->job_title) }}" placeholder="e.g., Full Stack Developer"
                            class="w-full form-input rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('job_title') border-red-500 @enderror">
                        @error('job_title')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="location"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Location</label>
                        <input type="text" id="location" name="location" value="{{ old('location', $user->location) }}"
                            placeholder="e.g., San Francisco, CA"
                            class="w-full form-input rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('location') border-red-500 @enderror">
                        @error('location')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-6">
                    <label for="bio" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Bio</label>
                    <textarea id="bio" name="bio" rows="4" placeholder="Tell us about yourself..."
                        class="w-full form-textarea rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('bio') border-red-500 @enderror">{{ old('bio', $user->bio) }}</textarea>
                    @error('bio')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Maximum 1000 characters</p>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="admin-card p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Contact Information</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Phone
                            Number</label>
                        <input type="tel" id="phone" name="phone" value="{{ old('phone', $user->phone) }}"
                            placeholder="e.g., +1 (555) 123-4567"
                            class="w-full form-input rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('phone') border-red-500 @enderror">
                        @error('phone')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="website"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Website</label>
                        <input type="url" id="website" name="website" value="{{ old('website', $user->website) }}"
                            placeholder="https://yourwebsite.com"
                            class="w-full form-input rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('website') border-red-500 @enderror">
                        @error('website')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Account Security -->
            <div class="admin-card p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Account Security</h2>

                <div
                    class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4 mb-4">
                    <div class="flex">
                        <svg class="w-5 h-5 text-yellow-400 mr-3 mt-0.5" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.996-.833-2.464 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z">
                            </path>
                        </svg>
                        <div>
                            <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">Password Change</h3>
                            <p class="text-sm text-yellow-700 dark:text-yellow-300 mt-1">
                                Use the "Change Password" button in your profile to update your password securely.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">Password</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Last updated: {{
                            $user->updated_at->format('M j, Y') }}</p>
                    </div>
                    <button type="button" onclick="openPasswordChangeModal()" class="btn-secondary">
                        Change Password
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Save Button -->
    <div class="mt-8 flex justify-end space-x-4">
        <a href="{{ route('admin.profile.show') }}" class="btn-secondary">
            Cancel Changes
        </a>
        <button type="submit" class="btn-primary">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            Save Profile
        </button>
    </div>
</form>

<!-- Password Change Modal -->
<div id="password-change-modal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closePasswordChangeModal()">
        </div>

        <div
            class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form id="password-change-form" onsubmit="handlePasswordChange(event)">
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="flex items-center mb-4">
                        <div
                            class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 dark:bg-blue-900 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z">
                                </path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Change Password</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Update your account password</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label for="current_password_modal"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Current
                                Password</label>
                            <input type="password" id="current_password_modal" name="current_password" required
                                class="w-full form-input rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <div id="current-password-error" class="text-red-500 text-sm mt-1 hidden"></div>
                        </div>

                        <div>
                            <label for="new_password"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">New
                                Password</label>
                            <input type="password" id="new_password" name="password" required minlength="8"
                                class="w-full form-input rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Minimum 8 characters</p>
                        </div>

                        <div>
                            <label for="confirm_password"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Confirm New
                                Password</label>
                            <input type="password" id="confirm_password" name="password_confirmation" required
                                class="w-full form-input rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <div id="password-match-error" class="text-red-500 text-sm mt-1 hidden"></div>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="btn-primary w-full sm:w-auto sm:ml-3" id="password-submit-btn">
                        Update Password
                    </button>
                    <button type="button" onclick="closePasswordChangeModal()"
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
<script src="{{ asset('js/media-picker.js') }}"></script>
<script>
    // Profile picture preview
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('profile-preview');
            preview.innerHTML = `<img src="${e.target.result}" alt="Profile Preview" class="w-32 h-32 rounded-full object-cover mx-auto">`;
            
            // Add remove button if it doesn't exist
            const container = preview.parentElement;
            if (!container.querySelector('.remove-btn')) {
                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'remove-btn absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 hover:bg-red-600';
                removeBtn.innerHTML = `
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                `;
                removeBtn.onclick = function() {
                    document.getElementById('profile_picture').value = '';
                    resetProfilePreview();
                };
                container.appendChild(removeBtn);
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function resetProfilePreview() {
    const preview = document.getElementById('profile-preview');
    preview.innerHTML = `
        <div class="w-32 h-32 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center mx-auto">
            <span class="text-3xl font-bold text-gray-600 dark:text-gray-300">{{ Auth::user()->initials }}</span>
        </div>
    `;
    
    // Remove the remove button
    const container = preview.parentElement;
    const removeBtn = container.querySelector('.remove-btn');
    if (removeBtn) {
        removeBtn.remove();
    }
}

// Media picker callback for profile picture
function selectProfilePicture(media) {
    if (media && media.url) {
        // Update preview
        const preview = document.getElementById('profile-preview');
        preview.innerHTML = `<img src="${media.url}" alt="Profile Preview" class="w-32 h-32 rounded-full object-cover mx-auto">`;
        
        // Create hidden input to store media ID
        let hiddenInput = document.getElementById('selected-media-id');
        if (!hiddenInput) {
            hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.id = 'selected-media-id';
            hiddenInput.name = 'media_id';
            document.querySelector('form').appendChild(hiddenInput);
        }
        hiddenInput.value = media.id;
        
        // Clear file input since we're using media picker
        document.getElementById('profile_picture').value = '';
        
        // Add remove button
        const container = preview.parentElement;
        if (!container.querySelector('.remove-btn')) {
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'remove-btn absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 hover:bg-red-600';
            removeBtn.innerHTML = `
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            `;
            removeBtn.onclick = function() {
                document.getElementById('profile_picture').value = '';
                hiddenInput.value = '';
                resetProfilePreview();
            };
            container.appendChild(removeBtn);
        }
    }
}

// Remove profile picture
function removeProfilePicture() {
    if (!confirm('Are you sure you want to remove your profile picture?')) {
        return;
    }
    
    fetch('{{ route("admin.profile.remove-picture") }}', {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            resetProfilePreview();
            showNotification('Profile picture removed successfully!', 'success');
        } else {
            showNotification(data.message || 'Failed to remove profile picture', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Failed to remove profile picture', 'error');
    });
}

// Password change modal
function openPasswordChangeModal() {
    document.getElementById('password-change-modal').classList.remove('hidden');
}

function closePasswordChangeModal() {
    document.getElementById('password-change-modal').classList.add('hidden');
    document.getElementById('password-change-form').reset();
    hideErrors();
}

function handlePasswordChange(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const newPassword = formData.get('password');
    const confirmPassword = formData.get('password_confirmation');
    
    // Clear previous errors
    hideErrors();
    
    // Validate password match
    if (newPassword !== confirmPassword) {
        showError('password-match-error', 'Passwords do not match');
        return;
    }
    
    // Submit form
    const submitBtn = document.getElementById('password-submit-btn');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Updating...';
    
    fetch('{{ route("admin.profile.password") }}', {
        method: 'PUT',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closePasswordChangeModal();
            showNotification('Password updated successfully!', 'success');
        } else {
            if (data.errors) {
                if (data.errors.current_password) {
                    showError('current-password-error', data.errors.current_password[0]);
                }
            } else {
                showNotification(data.message || 'Failed to update password', 'error');
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Failed to update password', 'error');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Update Password';
    });
}

function showError(elementId, message) {
    const errorElement = document.getElementById(elementId);
    if (errorElement) {
        errorElement.textContent = message;
        errorElement.classList.remove('hidden');
    }
}

function hideErrors() {
    document.querySelectorAll('[id$="-error"]').forEach(element => {
        element.classList.add('hidden');
    });
}

function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm ${
        type === 'success' ? 'bg-green-600' : 
        type === 'error' ? 'bg-red-600' : 
        'bg-blue-600'
    } text-white`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Password confirmation validation
document.addEventListener('DOMContentLoaded', function() {
    const confirmPasswordField = document.getElementById('confirm_password');
    if (confirmPasswordField) {
        confirmPasswordField.addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (confirmPassword && newPassword !== confirmPassword) {
                showError('password-match-error', 'Passwords do not match');
            } else {
                document.getElementById('password-match-error').classList.add('hidden');
            }
        });
    }
});
</script>
@endpush