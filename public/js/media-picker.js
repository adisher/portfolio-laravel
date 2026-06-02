function openMediaPicker(callback, options = {}) {
    const { type = 'image', multiple = false } = options;

    window.mediaPickerCallback = callback;

    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 z-50 overflow-y-auto';
    modal.innerHTML = `
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="this.parentElement.parentElement.remove()"></div>
            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                <div id="media-picker-content" class="p-6">
                    <div class="text-center">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
                        <p class="mt-2 text-gray-600">Loading media...</p>
                    </div>
                </div>
            </div>
        </div>
    `;

    document.body.appendChild(modal);

    // Load media picker content
    fetch(`/admin/media/picker?type=${type}&multiple=${multiple}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('media-picker-content').innerHTML = html;
        })
        .catch(error => {
            console.error('Error loading media picker:', error);
            modal.remove();
            alert('Failed to load media picker');
        });
}