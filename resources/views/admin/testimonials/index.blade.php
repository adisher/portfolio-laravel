@extends('layouts.admin')

@section('title', 'Testimonials - Admin Panel')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Testimonials</h1>
        <p class="text-gray-600 dark:text-gray-400">Manage client & colleague testimonials for the globe display</p>
    </div>
    <a href="{{ route('admin.testimonials.create') }}" class="btn-primary">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
        </svg>
        Add Testimonial
    </a>
</div>

<!-- Testimonials Table -->
<div class="admin-card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Person</th>
                    <th>Type</th>
                    <th>Location</th>
                    <th>Rating</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($testimonials as $testimonial)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td>
                        <div class="flex items-center space-x-3">
                            @if($testimonial->client_image)
                            <img src="{{ Storage::url($testimonial->client_image) }}" alt="{{ $testimonial->client_name }}"
                                class="w-10 h-10 rounded-full object-cover">
                            @else
                            <div class="w-10 h-10 rounded-full bg-teal-100 dark:bg-teal-900 flex items-center justify-center">
                                <span class="text-teal-600 dark:text-teal-400 font-bold">{{ substr($testimonial->client_name, 0, 1) }}</span>
                            </div>
                            @endif
                            <div>
                                <div class="font-medium text-gray-900 dark:text-white">{{ $testimonial->client_name }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $testimonial->client_position }}
                                    @if($testimonial->client_company)
                                    at {{ $testimonial->client_company }}
                                    @endif
                                </div>
                            </div>
                        </div>
                    </td>
                    <td>
                        @php
                            $typeColors = [
                                'client' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                                'colleague' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
                                'user' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                            ];
                        @endphp
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $typeColors[$testimonial->type ?? 'client'] ?? $typeColors['client'] }}">
                            {{ $testimonial->type === 'user' ? 'Product User' : ucfirst($testimonial->type ?? 'client') }}
                        </span>
                    </td>
                    <td>
                        @if($testimonial->country_code)
                        <div class="flex items-center space-x-2">
                            <span class="text-xl">{{ country_flag($testimonial->country_code) }}</span>
                            <span class="text-sm text-gray-600 dark:text-gray-400">
                                {{ $testimonial->city ? $testimonial->city . ', ' : '' }}{{ $testimonial->country_name ?? strtoupper($testimonial->country_code) }}
                            </span>
                        </div>
                        @if($testimonial->latitude && $testimonial->longitude)
                        <span class="text-xs text-green-600 dark:text-green-400">Has coordinates</span>
                        @else
                        <span class="text-xs text-yellow-600 dark:text-yellow-400">No coordinates</span>
                        @endif
                        @else
                        <span class="text-sm text-gray-400">No location set</span>
                        @endif
                    </td>
                    <td>
                        <div class="flex items-center">
                            @for($i = 1; $i <= 5; $i++)
                            <svg class="w-4 h-4 {{ $i <= $testimonial->rating ? 'text-yellow-400' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                            @endfor
                        </div>
                    </td>
                    <td>
                        @if($testimonial->is_published)
                        <span class="status-badge status-published">Published</span>
                        @else
                        <span class="status-badge status-draft">Draft</span>
                        @endif
                        @if($testimonial->is_featured)
                        <span class="ml-1 text-yellow-500" title="Featured">&#11088;</span>
                        @endif
                    </td>
                    <td>
                        <div class="flex items-center space-x-2">
                            <a href="{{ route('admin.testimonials.edit', $testimonial) }}"
                                class="text-gray-400 hover:text-green-600" title="Edit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                    </path>
                                </svg>
                            </a>
                            <form method="POST" action="{{ route('admin.testimonials.destroy', $testimonial) }}" class="inline"
                                onsubmit="return confirm('Are you sure you want to delete this testimonial?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-gray-400 hover:text-red-600" title="Delete">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                        </path>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-8 text-gray-500 dark:text-gray-400">
                        <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z">
                            </path>
                        </svg>
                        <p class="text-lg font-medium">No testimonials yet</p>
                        <p class="text-sm">Add your first client testimonial to display on the globe.</p>
                        <a href="{{ route('admin.testimonials.create') }}"
                            class="mt-4 inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            Add Testimonial
                        </a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($testimonials->hasPages())
    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
        {{ $testimonials->links() }}
    </div>
    @endif
</div>
@endsection
