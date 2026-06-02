@extends('layouts.admin')
@section('title', 'Create Product Page - ' . $project->title)

@section('content')
<div class="p-6" x-data="productPageForm()">
    {{-- Header --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.projects.product-pages.index', $project) }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Create Product Page</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $project->title }}</p>
        </div>
    </div>

    @if($errors->any())
    <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg text-red-700 dark:text-red-400 text-sm">
        <ul class="list-disc pl-4">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form action="{{ route('admin.projects.product-pages.store', $project) }}" method="POST">
        @csrf

        @include('admin.product-pages._form')

        <div class="flex justify-end gap-3 mt-6">
            <a href="{{ route('admin.projects.product-pages.index', $project) }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">Create Page</button>
        </div>
    </form>
</div>

<script>
function productPageForm() {
    return {
        type: '{{ old('type', 'setup') }}',
        iconOptions: ['star', 'shield', 'palette', 'chart', 'code', 'globe', 'lightning', 'lock', 'link', 'device', 'cloud', 'settings', 'rocket', 'download', 'users', 'refresh', 'check'],
        options: [{ title: '', description: '', icon: 'rocket', button_label: '', button_url: '', recommended: false }],
        steps: [{ title: '', description: '', items: '', button_label: '', button_url: '', guidance: '', note: '' }],
    };
}
</script>
@endsection
