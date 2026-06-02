@extends('layouts.admin')
@section('title', 'Edit Product Page - ' . $productPage->title)

@section('content')
<div class="p-6" x-data="productPageForm()">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.projects.product-pages.index', $project) }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Edit Product Page</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $project->title }} / {{ $productPage->title }}</p>
            </div>
        </div>
        <a href="{{ route('products.page', [$project->slug, $productPage->slug]) }}" target="_blank" class="btn-secondary text-sm">
            View Page
        </a>
    </div>

    @if(session('success'))
    <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg text-green-700 dark:text-green-400 text-sm">
        {{ session('success') }}
    </div>
    @endif

    @if($errors->any())
    <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg text-red-700 dark:text-red-400 text-sm">
        <ul class="list-disc pl-4">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form action="{{ route('admin.projects.product-pages.update', [$project, $productPage]) }}" method="POST">
        @csrf @method('PUT')

        @include('admin.product-pages._form')

        <div class="flex justify-end gap-3 mt-6">
            <a href="{{ route('admin.projects.product-pages.index', $project) }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">Update Page</button>
        </div>
    </form>
</div>

<script>
function productPageForm() {
    const content = @json($productPage->content ?? []);
    const type = '{{ old('type', $productPage->type) }}';
    return {
        type: type,
        iconOptions: ['star', 'shield', 'palette', 'chart', 'code', 'globe', 'lightning', 'lock', 'link', 'device', 'cloud', 'settings', 'rocket', 'download', 'users', 'refresh', 'check'],
        options: type === 'setup' ? (content.options || [{ title: '', description: '', icon: 'rocket', button_label: '', button_url: '', recommended: false }]) : [{ title: '', description: '', icon: 'rocket', button_label: '', button_url: '', recommended: false }],
        steps: type === 'deploy' ? (content.steps || [{ title: '', description: '', items: '', button_label: '', button_url: '', guidance: '', note: '' }]) : [{ title: '', description: '', items: '', button_label: '', button_url: '', guidance: '', note: '' }],
    };
}
</script>
@endsection
