@extends('layouts.admin')

@section('title', 'Contact Messages - Admin Panel')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Contact Messages</h1>
    <p class="text-gray-600 dark:text-gray-400">Manage inquiries and messages from visitors</p>
</div>

<!-- Filters -->
<div class="admin-card p-4 mb-6">
    <form method="GET" class="flex flex-wrap gap-4 items-end">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
            <select name="status" class="form-select rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                <option value="">All Messages</option>
                <option value="unread" {{ request('status')==='unread' ? 'selected' : '' }}>Unread</option>
                <option value="read" {{ request('status')==='read' ? 'selected' : '' }}>Read</option>
                <option value="replied" {{ request('status')==='replied' ? 'selected' : '' }}>Replied</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name or email..."
                class="form-input rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700">
        </div>
        <button type="submit" class="btn-secondary">Filter</button>
        @if(request()->hasAny(['status', 'search']))
        <a href="{{ route('admin.contacts.index') }}" class="text-gray-500 hover:text-gray-700">Clear</a>
        @endif
    </form>
</div>

<!-- Messages Table -->
<div class="admin-card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Contact</th>
                    <th>Subject</th>
                    <th>Message</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($contacts ?? \App\Models\Contact::latest()->paginate(15) as $contact)
                <tr
                    class="hover:bg-gray-50 dark:hover:bg-gray-700 {{ $contact->status === 'unread' ? 'bg-blue-50 dark:bg-blue-900/20' : '' }}">
                    <td>
                        <div class="flex items-center space-x-3">
                            <div
                                class="w-10 h-10 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center">
                                <span class="text-gray-600 dark:text-gray-300 font-medium text-sm">
                                    {{ substr($contact->name, 0, 1) }}
                                </span>
                            </div>
                            <div>
                                <div class="font-medium text-gray-900 dark:text-white">{{ $contact->name }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $contact->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="text-sm text-gray-900 dark:text-white">
                        {{ $contact->subject ?: 'No subject' }}
                    </td>
                    <td class="text-sm text-gray-700 dark:text-gray-300 max-w-xs">
                        <div class="truncate">{{ Str::limit($contact->message, 80) }}</div>
                    </td>
                    <td>
                        <span class="status-badge status-{{ $contact->status }}">
                            {{ ucfirst($contact->status) }}
                        </span>
                    </td>
                    <td class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $contact->created_at->format('M j, Y') }}
                        <div class="text-xs">{{ $contact->created_at->format('g:i A') }}</div>
                    </td>
                    <td>
                        <div class="flex items-center space-x-2">
                            <a href="{{ route('admin.contacts.show', $contact) }}"
                                class="text-gray-400 hover:text-blue-600" title="View">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                    </path>
                                </svg>
                            </a>
                            <form method="POST" action="{{ route('admin.contacts.destroy', $contact) }}" class="inline"
                                onsubmit="return confirm('Are you sure you want to delete this message?')">
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
                        <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                            </path>
                        </svg>
                        <p class="text-lg font-medium">No messages found</p>
                        <p class="text-sm">Contact messages will appear here when visitors submit the contact form.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(isset($contacts) && $contacts->hasPages())
    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
        {{ $contacts->links() }}
    </div>
    @endif
</div>
@endsection