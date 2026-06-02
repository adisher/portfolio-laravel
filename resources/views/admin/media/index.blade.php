@extends('layouts.admin')

@section('title', 'Media Library - Admin Panel')

@section('content')
<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Media Library</h1>
            <p class="text-gray-600 dark:text-gray-400">Manage your portfolio media files</p>
        </div>
        <button onclick="openUploadModal()" class="btn-primary">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
            </svg>
            Upload Files
        </button>
    </div>
</div>

<!-- Storage Stats -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="admin-card p-4 text-center">
        <div class="text-2xl font-bold text-blue-600">{{ $stats['total_files'] }}</div>
        <div class="text-sm text-gray-500">Total Files</div>
    </div>
    <div class="admin-card p-4 text-center">
        <div class="text-2xl font-bold text-green-600">{{ $stats['formatted_size'] }}</div>
        <div class="text-sm text-gray-500">Storage Used</div>
    </div>
    <div class="admin-card p-4 text-center">
        <div class="text-2xl font-bold text-purple-600">{{ $stats['image_count'] }}</div>
        <div class="text-sm text-gray-500">Images</div>
    </div>
    <div class="admin-card p-4 text-center">
        <div class="text-2xl font-bold text-orange-600">{{ $stats['recent_uploads'] }}</div>
        <div class="text-sm text-gray-500">Recent Uploads</div>
    </div>
</div>

<!-- Filters and Search -->
<div class="admin-card p-4 mb-6">
    <div class="flex flex-wrap gap-4 items-center">
        <!-- Folder Navigation -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Folder</label>
            <select id="folder-select" class="form-select rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                <option value="/">All Folders</option>
                @foreach($folders as $folderOption)
                <option value="{{ $folderOption }}" {{ $folder===$folderOption ? 'selected' : '' }}>
                    {{ $folderOption === '/' ? 'Root' : ltrim($folderOption, '/') }}
                </option>
                @endforeach
            </select>
        </div>

        <!-- File Type Filter -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
            <select id="type-select"
                class="form-select rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                <option value="">All Types</option>
                <option value="image" {{ $type==='image' ? 'selected' : '' }}>Images</option>
                <option value="application" {{ $type==='application' ? 'selected' : '' }}>Documents</option>
            </select>
        </div>

        <!-- Search -->
        <div class="flex-1">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search</label>
            <input type="text" id="search-input" value="{{ $search }}" placeholder="Search files..."
                class="w-full form-input rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700">
        </div>

        <!-- Actions -->
        <div class="flex space-x-2 items-end">
            <button onclick="createFolder()" class="btn-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                New Folder
            </button>
            <button onclick="refreshMedia()" class="btn-secondary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                    </path>
                </svg>
            </button>
        </div>
    </div>
</div>

<!-- Media Grid -->
<div class="admin-card p-6">
    @if($media->count())
    <div id="media-grid" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
        @foreach($media as $file)
        <div class="media-item relative group cursor-pointer border-2 border-transparent hover:border-blue-500 rounded-lg p-2 transition-all"
            data-id="{{ $file->id }}">
            <div class="aspect-square bg-gray-100 dark:bg-gray-700 rounded-lg overflow-hidden mb-2">
                @if($file->is_image)
                <img src="{{ $file->getVariantUrl('thumbnail') }}" alt="{{ $file->alt_text ?: $file->name }}"
                    class="w-full h-full object-cover">
                @else
                <div class="w-full h-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                        </path>
                    </svg>
                </div>
                @endif
            </div>

            <div class="text-xs text-gray-600 dark:text-gray-400 text-center">
                <p class="truncate">{{ $file->name }}</p>
                <p class="text-gray-500">{{ $file->formatted_size }}</p>
            </div>

            <!-- Actions overlay -->
            <div
                class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-50 transition-all rounded-lg flex items-center justify-center opacity-0 group-hover:opacity-100">
                <div class="flex space-x-2">
                    <button onclick="viewMedia({{ $file->id }})"
                        class="bg-white text-gray-900 p-2 rounded-full hover:bg-gray-100">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </button>
                    <button onclick="editMedia({{ $file->id }})"
                        class="bg-white text-gray-900 p-2 rounded-full hover:bg-gray-100">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                            </path>
                        </svg>
                    </button>
                    <button onclick="deleteMedia({{ $file->id }})"
                        class="bg-red-600 text-white p-2 rounded-full hover:bg-red-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                            </path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Pagination -->
    @if($media->hasPages())
    <div class="mt-6">
        {{ $media->appends(request()->query())->links() }}
    </div>
    @endif
    @else
    <div class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
            </path>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No media files</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by uploading your first file.</p>
        <div class="mt-6">
            <button onclick="openUploadModal()" class="btn-primary">
                Upload Files
            </button>
        </div>
    </div>
    @endif
</div>

<!-- Upload Modal -->
<div id="upload-modal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeUploadModal()"></div>

        <div
            class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Upload Files</h3>

                <div id="dropzone"
                    class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-8 text-center hover:border-blue-500 transition-colors cursor-pointer">
                    <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                        <path
                            d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                    </svg>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        <span class="font-medium">Click to upload</span> or drag and drop
                    </p>
                    <p class="text-xs text-gray-500">PNG, JPG, GIF up to 10MB</p>
                </div>

                <input type="file" id="file-input" multiple accept="image/*,.pdf,.doc,.docx" class="hidden">

                <div id="upload-progress" class="hidden mt-4">
                    <div class="bg-gray-200 rounded-full h-2">
                        <div id="progress-bar" class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                            style="width: 0%"></div>
                    </div>
                    <p id="upload-status" class="text-sm text-gray-600 mt-2">Uploading...</p>
                </div>
            </div>

            <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button onclick="startUpload()" id="upload-btn" class="btn-primary w-full sm:w-auto sm:ml-3" disabled>
                    Upload
                </button>
                <button onclick="closeUploadModal()" class="btn-secondary w-full sm:w-auto mt-3 sm:mt-0">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    let selectedFiles = [];
let currentFolder = '{{ $folder }}';

// Upload Modal Functions
function openUploadModal() {
    document.getElementById('upload-modal').classList.remove('hidden');
    selectedFiles = [];
    document.getElementById('file-input').value = '';
    resetDropzoneText();
}

function closeUploadModal() {
    document.getElementById('upload-modal').classList.add('hidden');
    selectedFiles = [];
    document.getElementById('file-input').value = '';
    document.getElementById('upload-progress').classList.add('hidden');
    document.getElementById('progress-bar').style.width = '0%';
    resetDropzoneText();
}

function resetDropzoneText() {
    const dropzone = document.getElementById('dropzone');
    dropzone.innerHTML = `
        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
        </svg>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
            <span class="font-medium">Click to upload</span> or drag and drop
        </p>
        <p class="text-xs text-gray-500">PNG, JPG, GIF up to 10MB</p>
    `;
}

// Initialize event listeners
document.addEventListener('DOMContentLoaded', function() {
    const dropzone = document.getElementById('dropzone');
    const fileInput = document.getElementById('file-input');
    
    dropzone.addEventListener('click', function(e) {
        e.preventDefault();
        fileInput.click();
    });

    fileInput.addEventListener('change', function(e) {
        selectedFiles = Array.from(e.target.files);
        updateDropzoneText();
    });

    dropzone.addEventListener('dragover', function(e) {
        e.preventDefault();
        e.stopPropagation();
        this.classList.add('border-blue-500', 'bg-blue-50');
    });

    dropzone.addEventListener('dragleave', function(e) {
        e.preventDefault();
        e.stopPropagation();
        this.classList.remove('border-blue-500', 'bg-blue-50');
    });

    dropzone.addEventListener('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        this.classList.remove('border-blue-500', 'bg-blue-50');
        selectedFiles = Array.from(e.dataTransfer.files);
        updateDropzoneText();
    });
});

function updateDropzoneText() {
    const dropzone = document.getElementById('dropzone');
    if (selectedFiles.length > 0) {
        dropzone.innerHTML = `
            <svg class="mx-auto h-8 w-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                <span class="font-medium">${selectedFiles.length} file(s) selected</span>
            </p>
            <div class="mt-2 text-xs text-gray-500">
                ${selectedFiles.map(f => f.name).slice(0, 3).join(', ')}${selectedFiles.length > 3 ? '...' : ''}
            </div>
        `;
        document.getElementById('upload-btn').disabled = false;
    } else {
        resetDropzoneText();
        document.getElementById('upload-btn').disabled = true;
    }
}

function startUpload() {
    if (selectedFiles.length === 0) {
        alert('Please select files to upload');
        return;
    }

    const formData = new FormData();
    selectedFiles.forEach((file) => {
        formData.append('files[]', file);
    });
    formData.append('folder', currentFolder);

    // Show progress
    const progressContainer = document.getElementById('upload-progress');
    const progressBar = document.getElementById('progress-bar');
    const uploadStatus = document.getElementById('upload-status');
    const uploadBtn = document.getElementById('upload-btn');
    
    progressContainer.classList.remove('hidden');
    uploadBtn.disabled = true;
    uploadBtn.textContent = 'Uploading...';
    
    // Simulate progress (since we can't get real progress from FormData)
    let progress = 0;
    const progressInterval = setInterval(() => {
        progress += 10;
        if (progress <= 90) {
            progressBar.style.width = progress + '%';
            uploadStatus.textContent = `Uploading... ${progress}%`;
        }
    }, 200);

    fetch('{{ route("admin.media.store") }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        clearInterval(progressInterval);
        progressBar.style.width = '100%';
        uploadStatus.textContent = 'Upload complete!';
        
        if (data.success) {
            setTimeout(() => {
                closeUploadModal();
                showNotification('Files uploaded successfully!', 'success');
                location.reload();
            }, 1000);
        } else {
            showNotification(data.error || 'Upload failed', 'error');
        }
    })
    .catch(error => {
        clearInterval(progressInterval);
        console.error('Upload error:', error);
        showNotification('Upload failed: ' + error.message, 'error');
    })
    .finally(() => {
        uploadBtn.disabled = false;
        uploadBtn.textContent = 'Upload';
        setTimeout(() => {
            progressContainer.classList.add('hidden');
            progressBar.style.width = '0%';
        }, 2000);
    });
}

// Media View Modal
function showMediaModal(media) {
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 z-50 overflow-y-auto';
    modal.innerHTML = `
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="this.parentElement.parentElement.remove()"></div>
            
            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">${media.name}</h3>
                        <button onclick="this.closest('.fixed').remove()" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Media Preview -->
                        <div class="text-center">
                            ${media.is_image ? `
                                <img src="${media.url}" alt="${media.alt_text || media.name}" class="w-full h-auto rounded-lg shadow-md max-h-96 object-contain mx-auto">
                            ` : `
                                <div class="flex items-center justify-center h-64 bg-gray-100 dark:bg-gray-700 rounded-lg">
                                    <svg class="w-20 h-20 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </div>
                            `}
                        </div>
                        
                        <!-- Media Details -->
                        <div>
                            <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">File Details</h4>
                            <div class="space-y-3">
                                <div class="grid grid-cols-3 gap-2">
                                    <span class="font-medium text-gray-500 dark:text-gray-400">Name:</span>
                                    <span class="col-span-2 text-gray-900 dark:text-white break-all">${media.name}</span>
                                </div>
                                <div class="grid grid-cols-3 gap-2">
                                    <span class="font-medium text-gray-500 dark:text-gray-400">Size:</span>
                                    <span class="col-span-2 text-gray-900 dark:text-white">${media.formatted_size}</span>
                                </div>
                                <div class="grid grid-cols-3 gap-2">
                                    <span class="font-medium text-gray-500 dark:text-gray-400">Type:</span>
                                    <span class="col-span-2 text-gray-900 dark:text-white">${media.mime_type}</span>
                                </div>
                                ${media.metadata && media.metadata.width ? `
                                    <div class="grid grid-cols-3 gap-2">
                                        <span class="font-medium text-gray-500 dark:text-gray-400">Dimensions:</span>
                                        <span class="col-span-2 text-gray-900 dark:text-white">${media.metadata.width} × ${media.metadata.height} px</span>
                                    </div>
                                ` : ''}
                                <div class="grid grid-cols-3 gap-2">
                                    <span class="font-medium text-gray-500 dark:text-gray-400">Uploaded:</span>
                                    <span class="col-span-2 text-gray-900 dark:text-white">${new Date(media.created_at).toLocaleDateString()}</span>
                                </div>
                                <div class="grid grid-cols-3 gap-2">
                                    <span class="font-medium text-gray-500 dark:text-gray-400">Folder:</span>
                                    <span class="col-span-2 text-gray-900 dark:text-white">${media.folder === '/' ? 'Root' : media.folder}</span>
                                </div>
                                ${media.alt_text ? `
                                    <div class="grid grid-cols-3 gap-2">
                                        <span class="font-medium text-gray-500 dark:text-gray-400">Alt Text:</span>
                                        <span class="col-span-2 text-gray-900 dark:text-white">${media.alt_text}</span>
                                    </div>
                                ` : ''}
                                ${media.description ? `
                                    <div class="grid grid-cols-3 gap-2">
                                        <span class="font-medium text-gray-500 dark:text-gray-400">Description:</span>
                                        <span class="col-span-2 text-gray-900 dark:text-white">${media.description}</span>
                                    </div>
                                ` : ''}
                            </div>
                            
                            <!-- URL Copy -->
                            <div class="mt-4 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">File URL:</label>
                                <div class="flex">
                                    <input type="text" value="${media.url}" readonly class="flex-1 text-sm px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-l-md bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                                    <button onclick="copyToClipboard('${media.url}')" class="px-3 py-2 bg-blue-600 text-white text-sm rounded-r-md hover:bg-blue-700">
                                        Copy
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Actions -->
                    <div class="mt-6 flex justify-between">
                        <div class="flex space-x-2">
                            <button onclick="editMedia(${media.id}); this.closest('.fixed').remove();" class="btn-secondary">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                                Edit
                            </button>
                            <button onclick="deleteMedia(${media.id}); this.closest('.fixed').remove();" class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700">
                                <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                                Delete
                            </button>
                        </div>
                        <a href="${media.url}" download="${media.name}" class="btn-primary">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Download
                        </a>
                    </div>
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

// Media Edit Modal
function showEditModal(media) {
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 z-50 overflow-y-auto';
    modal.innerHTML = `
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="this.parentElement.parentElement.remove()"></div>
            
            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form onsubmit="updateMedia(event, ${media.id})">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Edit Media</h3>
                            <button type="button" onclick="this.closest('.fixed').remove()" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        
                        <!-- Media Preview -->
                        <div class="mb-4 text-center">
                            ${media.is_image ? `
                                <img src="${media.url}" alt="${media.name}" class="h-32 w-auto rounded-lg mx-auto">
                            ` : `
                                <div class="h-20 w-20 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center mx-auto">
                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </div>
                            `}
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">${media.name}</p>
                        </div>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Alt Text</label>
                                <input type="text" name="alt_text" value="${media.alt_text || ''}" placeholder="Describe this image for accessibility" class="w-full form-input rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Description</label>
                                <textarea name="description" rows="3" placeholder="Optional description of this file" class="w-full form-textarea rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">${media.description || ''}</textarea>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Folder</label>
                                <select name="folder" class="w-full form-select rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    <option value="/">Root</option>
                                    @foreach($folders as $folderOption)
                                    <option value="{{ $folderOption }}" ${media.folder === '{{ $folderOption }}' ? 'selected' : ''}>
                                        {{ $folderOption === '/' ? 'Root' : ltrim($folderOption, '/') }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="btn-primary w-full sm:w-auto sm:ml-3">
                            Save Changes
                        </button>
                        <button type="button" onclick="this.closest('.fixed').remove()" class="btn-secondary w-full sm:w-auto mt-3 sm:mt-0">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

// Update media function
function updateMedia(event, id) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const data = Object.fromEntries(formData);
    
    fetch(`/admin/media/${id}`, {
        method: 'PUT',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            event.target.closest('.fixed').remove();
            showNotification('Media updated successfully!', 'success');
            location.reload();
        } else {
            showNotification(data.error || 'Failed to update media', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Failed to update media', 'error');
    });
}

// Media actions
function viewMedia(id) {
    fetch(`/admin/media/${id}`)
        .then(response => response.json())
        .then(media => {
            showMediaModal(media);
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Failed to load media details', 'error');
        });
}

function editMedia(id) {
    fetch(`/admin/media/${id}`)
        .then(response => response.json())
        .then(media => {
            showEditModal(media);
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Failed to load media details', 'error');
        });
}

function deleteMedia(id) {
    if (!confirm('Are you sure you want to delete this file? This action cannot be undone.')) {
        return;
    }

    fetch(`/admin/media/${id}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Media deleted successfully!', 'success');
            location.reload();
        } else {
            showNotification(data.error || 'Failed to delete file', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Failed to delete file', 'error');
    });
}

// Utility functions
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showNotification('URL copied to clipboard!', 'success');
    }).catch(() => {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        showNotification('URL copied to clipboard!', 'success');
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

// Filter functions
function updateFilters() {
    const folder = document.getElementById('folder-select').value;
    const type = document.getElementById('type-select').value;
    const search = document.getElementById('search-input').value;
    
    const params = new URLSearchParams();
    if (folder !== '/') params.append('folder', folder);
    if (type) params.append('type', type);
    if (search) params.append('search', search);
    
    window.location.href = '{{ route("admin.media.index") }}' + (params.toString() ? '?' + params.toString() : '');
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function createFolder() {
    const name = prompt('Folder name:');
    if (!name) return;
    
    fetch('{{ route("admin.media.folder.create") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            name: name,
            parent: currentFolder
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Folder created successfully!', 'success');
            location.reload();
        } else {
            showNotification(data.error || 'Failed to create folder', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Failed to create folder', 'error');
    });
}

function refreshMedia() {
    location.reload();
}

// Initialize filters
document.getElementById('folder-select')?.addEventListener('change', updateFilters);
document.getElementById('type-select')?.addEventListener('change', updateFilters);
document.getElementById('search-input')?.addEventListener('input', debounce(updateFilters, 500));
</script>
@endpush