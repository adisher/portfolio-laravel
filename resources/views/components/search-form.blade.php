<form method="GET" action="{{ route('blog.search') }}" class="relative">
    <div class="relative">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Search articles..."
            class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
            autocomplete="off">
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
        </div>
        @if(request('q'))
        <button type="button" onclick="clearSearch()" class="absolute inset-y-0 right-0 pr-3 flex items-center">
            <svg class="h-5 w-5 text-gray-400 hover:text-gray-600" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
        @endif
    </div>

    @if(request('q'))
    <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">
        {{ $posts->total() ?? 0 }} result{{ ($posts->total() ?? 0) !== 1 ? 's' : '' }} for "{{ request('q') }}"
        <button type="button" onclick="clearSearch()" class="ml-2 text-blue-600 hover:text-blue-500">
            Clear search
        </button>
    </div>
    @endif
</form>

<script>
    function clearSearch() {
    window.location.href = '{{ route('blog.index') }}';
}
</script>