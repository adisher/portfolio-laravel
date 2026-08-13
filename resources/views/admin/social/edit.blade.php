@extends('layouts.admin')

@section('title', 'Edit Social Account - Admin Panel')

@section('content')
<div class="mb-6">
    <div class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400 mb-2">
        <a href="{{ route('admin.social.index') }}" class="hover:text-gray-700">Social Accounts</a>
        <span>›</span>
        <span>{{ $account->name }}</span>
    </div>
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Edit Account</h1>
</div>

<form action="{{ route('admin.social.update', $account) }}" method="POST">
    @csrf
    @method('PUT')
    @include('admin.social._form')
</form>
@endsection
