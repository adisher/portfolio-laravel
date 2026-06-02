@extends('layouts.admin')

@section('title', 'Contact Message - Admin Panel')

@section('content')
<div class="mb-6">
    <div class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400 mb-2">
        <a href="{{ route('admin.contacts.index') }}" class="hover:text-gray-700">Contact Messages</a>
        <span>›</span>
        <span>{{ $contact->name }}</span>
    </div>
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Contact Message</h1>
        <div class="flex items-center space-x-2">
            <span class="status-badge status-{{ $contact->status }}">
                {{ ucfirst($contact->status) }}
            </span>
            @if($contact->status === 'unread')
            <form method="POST" action="{{ route('admin.contacts.mark-read', $contact) }}" class="inline">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn-secondary text-sm">
                    Mark as Read
                </button>
            </form>
            @endif
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Message Content -->
    <div class="lg:col-span-2">
        <div class="admin-card p-6">
            <div class="border-b border-gray-200 dark:border-gray-700 pb-6 mb-6">
                <div class="flex items-start space-x-4">
                    <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                        <span class="text-blue-600 dark:text-blue-400 font-medium text-lg">
                            {{ substr($contact->name, 0, 1) }}
                        </span>
                    </div>
                    <div class="flex-1">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">{{ $contact->name }}</h2>
                        <p class="text-gray-600 dark:text-gray-300">{{ $contact->email }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $contact->created_at->format('M j, Y \a\t g:i A') }}
                            ({{ $contact->created_at->diffForHumans() }})
                        </p>
                    </div>
                </div>
            </div>

            @if($contact->subject)
            <div class="mb-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Subject</h3>
                <p class="text-gray-700 dark:text-gray-300">{{ $contact->subject }}</p>
            </div>
            @endif

            <div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Message</h3>
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                    <div class="prose dark:prose-invert max-w-none">
                        {!! nl2br(e($contact->message)) !!}
                    </div>
                </div>
            </div>

            @if($contact->admin_notes)
            <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Admin Notes</h3>
                <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4">
                    <div class="prose dark:prose-invert max-w-none">
                        {!! nl2br(e($contact->admin_notes)) !!}
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Quick Actions -->
        <div class="admin-card p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Quick Actions</h3>

            <div class="space-y-3">
                <a href="mailto:{{ $contact->email }}?subject=Re: {{ $contact->subject ?: 'Your message' }}"
                    class="w-full btn-primary text-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                        </path>
                    </svg>
                    Reply via Email
                </a>

                @if($contact->status === 'unread')
                <form method="POST" action="{{ route('admin.contacts.mark-read', $contact) }}" class="w-full">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="w-full btn-secondary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Mark as Read
                    </button>
                </form>
                @endif

                <button onclick="toggleNotesForm()" class="w-full btn-secondary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                        </path>
                    </svg>
                    {{ $contact->admin_notes ? 'Edit Notes' : 'Add Notes' }}
                </button>
            </div>
        </div>

        <!-- Admin Notes Form -->
        <div class="admin-card p-6" id="notes-form"
            style="{{ $contact->admin_notes ? 'display: block;' : 'display: none;' }}">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Admin Notes</h3>

            <form method="POST" action="{{ route('admin.contacts.update-notes', $contact) }}">
                @csrf
                @method('PATCH')
                <div class="space-y-4">
                    <div>
                        <textarea name="admin_notes" rows="6"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            placeholder="Add internal notes about this contact...">{{ old('admin_notes', $contact->admin_notes) }}</textarea>
                    </div>
                    <div class="flex space-x-2">
                        <button type="submit" class="flex-1 btn-primary">
                            {{ $contact->admin_notes ? 'Update Notes' : 'Save Notes' }}
                        </button>
                        <button type="button" onclick="toggleNotesForm()" class="btn-secondary">
                            Cancel
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Contact Details -->
        <div class="admin-card p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Contact Details</h3>

            <dl class="space-y-3">
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Name</dt>
                    <dd class="text-sm text-gray-900 dark:text-white">{{ $contact->name }}</dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Email</dt>
                    <dd class="text-sm text-gray-900 dark:text-white">
                        <a href="mailto:{{ $contact->email }}" class="text-blue-600 hover:text-blue-500">
                            {{ $contact->email }}
                        </a>
                    </dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Received</dt>
                    <dd class="text-sm text-gray-900 dark:text-white">{{ $contact->created_at->format('M j, Y g:i A') }}
                    </dd>
                </div>

                @if($contact->read_at)
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Read At</dt>
                    <dd class="text-sm text-gray-900 dark:text-white">{{ $contact->read_at->format('M j, Y g:i A') }}
                    </dd>
                </div>
                @endif

                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
                    <dd class="text-sm">
                        <span class="status-badge status-{{ $contact->status }}">
                            {{ ucfirst($contact->status) }}
                        </span>
                    </dd>
                </div>
            </dl>
        </div>

        <!-- Danger Zone -->
        <div class="admin-card p-6 border-red-200 dark:border-red-800">
            <h3 class="text-lg font-medium text-red-600 dark:text-red-400 mb-4">Danger Zone</h3>

            <form method="POST" action="{{ route('admin.contacts.destroy', $contact) }}"
                onsubmit="return confirm('Are you sure you want to delete this message? This action cannot be undone.')">
                @csrf
                @method('DELETE')
                <button type="submit"
                    class="w-full px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors">
                    <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                        </path>
                    </svg>
                    Delete Message
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function toggleNotesForm() {
    const notesForm = document.getElementById('notes-form');
    notesForm.style.display = notesForm.style.display === 'none' ? 'block' : 'none';
}
</script>
@endpush