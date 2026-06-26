@extends('layouts.admin')

@section('title', 'Edit Work Item - Admin Panel')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Edit: {{ $workItem->name }}</h1>
    <div class="flex gap-2">
        <a href="{{ route('admin.work-items.show', $workItem) }}" class="btn-secondary text-sm">View Manual</a>
        <a href="{{ route('admin.work-items.index') }}" class="btn-secondary text-sm">Back</a>
    </div>
</div>

<form action="{{ route('admin.work-items.update', $workItem) }}" method="POST">
    @csrf
    @method('PUT')
    @include('admin.work-items._form')
    <div class="flex justify-between gap-3">
        <button form="delete-work-item" class="text-red-500 hover:text-red-700 text-sm">Delete</button>
        <div class="flex gap-3">
            <a href="{{ route('admin.work-items.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">Save Changes</button>
        </div>
    </div>
</form>

<form id="delete-work-item" action="{{ route('admin.work-items.destroy', $workItem) }}" method="POST"
    onsubmit="return confirm('Delete this work item?')">
    @csrf @method('DELETE')
</form>
@endsection
