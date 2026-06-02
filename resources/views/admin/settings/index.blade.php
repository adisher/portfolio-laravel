@extends('layouts.admin')

@section('title', 'Site Settings - Admin Panel')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Site Settings</h1>
    <p class="text-gray-600 dark:text-gray-400">Configure your portfolio website settings</p>
</div>

<form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    
    <div class="space-y-8">
        @foreach($settingsGrouped as $group => $settings)
        <div class="admin-card p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6 capitalize border-b border-gray-200 dark:border-gray-700 pb-2">
                {{ str_replace('_', ' ', $group) }} Settings
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach($settings as $setting)
                <div class="{{ in_array($setting->type, ['text', 'file']) ? 'md:col-span-2' : 'md:col-span-1' }}">
                    <label for="{{ $setting->key }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ $setting->label }}
                        @if($setting->description)
                        <span class="text-xs text-gray-500 dark:text-gray-400 block font-normal">
                            {{ $setting->description }}
                        </span>
                        @endif
                    </label>
                    
                    @if($setting->type === 'boolean')
                        <div class="flex items-center">
                            <input type="checkbox" 
                                   id="{{ $setting->key }}" 
                                   name="{{ $setting->key }}" 
                                   value="1"
                                   {{ $setting->display_value ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Enable this feature</span>
                        </div>
                    
                    @elseif($setting->type === 'text')
                        <textarea id="{{ $setting->key }}" 
                                  name="{{ $setting->key }}" 
                                  rows="3"
                                  class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error($setting->key) border-red-500 @enderror">{{ old($setting->key, $setting->value) }}</textarea>
                    
                    @elseif($setting->type === 'file')
                        <div class="space-y-2">
                            @if($setting->value)
                            <div class="mb-3">
                                <img src="{{ Storage::url($setting->value) }}" alt="{{ $setting->label }}" class="h-20 w-20 object-cover rounded border">
                                <p class="text-xs text-gray-500 mt-1">Current {{ strtolower($setting->label) }}</p>
                            </div>
                            @endif
                            <input type="file" 
                                   id="{{ $setting->key }}" 
                                   name="{{ $setting->key }}"
                                   accept="image/*"
                                   class="w-full @error($setting->key) border-red-500 @enderror">
                        </div>
                    
                    @elseif($setting->options)
                        <select id="{{ $setting->key }}" 
                                name="{{ $setting->key }}"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error($setting->key) border-red-500 @enderror">
                            @foreach($setting->options as $value => $label)
                            <option value="{{ $value }}" {{ old($setting->key, $setting->value) == $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                            @endforeach
                        </select>
                    
                    @else
                        <input type="{{ $setting->type === 'email' ? 'email' : ($setting->type === 'url' ? 'url' : 'text') }}" 
                               id="{{ $setting->key }}" 
                               name="{{ $setting->key }}" 
                               value="{{ old($setting->key, $setting->value) }}"
                               placeholder="{{ $setting->description }}"
                               class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error($setting->key) border-red-500 @enderror">
                    @endif
                    
                    @error($setting->key)
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
    
    <!-- Save Button -->
    <div class="mt-8 flex justify-end space-x-4">
        <button type="button" onclick="resetForm()" class="btn-secondary">
            Reset Changes
        </button>
        <button type="submit" class="btn-primary">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            Save Settings
        </button>
    </div>
</form>

@endsection

@push('scripts')
<script>
function resetForm() {
    if (confirm('Are you sure you want to reset all changes?')) {
        document.querySelector('form').reset();
        // Reload to get original values
        window.location.reload();
    }
}

// Form enhancement - show file name when file is selected
document.querySelectorAll('input[type="file"]').forEach(input => {
    input.addEventListener('change', function(e) {
        const fileName = e.target.files[0]?.name;
        if (fileName) {
            const label = document.querySelector(`label[for="${e.target.id}"]`);
            const existingSpan = label.querySelector('.file-name');
            if (existingSpan) {
                existingSpan.remove();
            }
            
            const span = document.createElement('span');
            span.className = 'file-name text-sm text-blue-600 block mt-1';
            span.textContent = `Selected: ${fileName}`;
            label.appendChild(span);
        }
    });
});
</script>
@endpush