@extends('layouts.admin')

@section('title', 'New Work Item - Admin Panel')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">New Work Item</h1>
    <a href="{{ route('admin.work-items.index') }}" class="btn-secondary text-sm">Back</a>
</div>

<form action="{{ route('admin.work-items.store') }}" method="POST">
    @csrf
    @include('admin.work-items._form')
    <div class="flex justify-end gap-3">
        <a href="{{ route('admin.work-items.index') }}" class="btn-secondary">Cancel</a>
        <button type="submit" class="btn-primary">Create Work Item</button>
    </div>
</form>
@endsection
