<div class="media-picker">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Select Media</h3>
        <div class="flex space-x-2">
            <button onclick="openUploadInPicker()" class="btn-secondary text-sm">
                Upload New
            </button>
            <button onclick="closeMediaPicker()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                    </path>
                </svg>
            </button>
        </div>
    </div>

    <div class="grid grid-cols-4 md:grid-cols-6 gap-3 max-h-96 overflow-y-auto">
        @foreach($media as $file)
        <div class="media-picker-item cursor-pointer border-2 border-transparent hover:border-blue-500 rounded-lg p-2 transition-all {{ $multiple ? '' : 'single-select' }}"
            data-id="{{ $file->id }}" data-url="{{ $file->url }}" data-name="{{ $file->name }}">
            <div class="aspect-square bg-gray-100 dark:bg-gray-700 rounded-lg overflow-hidden mb-1">
                @if($file->is_image)
                <img src="{{ $file->getVariantUrl('thumbnail') }}" alt="{{ $file->name }}"
                    class="w-full h-full object-cover">
                @else
                <div class="w-full h-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                        </path>
                    </svg>
                </div>
                @endif
            </div>
            <p class="text-xs text-gray-600 dark:text-gray-400 text-center truncate">{{ $file->name }}</p>
        </div>
        @endforeach
    </div>

    @if($media->hasPages())
    <div class="mt-4">
        {{ $media->appends(['type' => $type, 'multiple' => $multiple])->links() }}
    </div>
    @endif

    <div class="mt-4 flex justify-end space-x-2">
        <button onclick="closeMediaPicker()" class="btn-secondary">Cancel</button>
        <button onclick="selectMedia()" class="btn-primary" disabled id="select-btn">
            Select{{ $multiple ? ' Files' : ' File' }}
        </button>
    </div>
</div>

<script>
    let selectedMedia = [];
const isMultiple = {{ $multiple ? 'true' : 'false' }};

document.querySelectorAll('.media-picker-item').forEach(item => {
    item.addEventListener('click', function() {
        const id = this.dataset.id;
        
        if (isMultiple) {
            if (this.classList.contains('border-blue-500')) {
                // Deselect
                this.classList.remove('border-blue-500', 'bg-blue-50');
                selectedMedia = selectedMedia.filter(media => media.id !== id);
            } else {
                // Select
                this.classList.add('border-blue-500', 'bg-blue-50');
                selectedMedia.push({
                    id: id,
                    url: this.dataset.url,
                    name: this.dataset.name
                });
            }
        } else {
            // Single select - remove previous selection
            document.querySelectorAll('.media-picker-item').forEach(item => {
                item.classList.remove('border-blue-500', 'bg-blue-50');
            });
            
            this.classList.add('border-blue-500', 'bg-blue-50');
            selectedMedia = [{
                id: id,
                url: this.dataset.url,
                name: this.dataset.name
            }];
        }
        
        document.getElementById('select-btn').disabled = selectedMedia.length === 0;
    });
});

function selectMedia() {
    if (window.mediaPickerCallback) {
        window.mediaPickerCallback(isMultiple ? selectedMedia : selectedMedia[0]);
    }
    closeMediaPicker();
}

function closeMediaPicker() {
    document.querySelector('.media-picker').closest('.fixed').remove();
}
</script>