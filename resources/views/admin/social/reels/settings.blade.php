@extends('layouts.admin')

@section('title', 'Studio settings - Reels')

@section('content')
<div class="mb-6">
    <div class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400 mb-2">
        <a href="{{ route('admin.social.reels.index') }}" class="hover:text-gray-700">Reels</a>
        <span>›</span>
        <span>Studio settings</span>
    </div>
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Studio settings</h1>
    <p class="text-gray-600 dark:text-gray-400">One-time setup so reels can be rendered with your voice.</p>
</div>

@if($errors->any())
    <div class="mb-6 p-4 rounded-lg bg-red-50 dark:bg-red-900 text-red-700 dark:text-red-200 text-sm">
        @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
    </div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    {{-- Voice --}}
    <div class="admin-card p-6">
        <div class="flex items-center justify-between mb-1">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white">1. Your voice</h2>
            <span class="text-xs px-2 py-1 rounded-full {{ $settings->hasVoice() ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }}">
                {{ $settings->hasVoice() ? 'Uploaded' : 'Not set' }}
            </span>
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Every reel is spoken in a clone of this recording. It stays private on your server and is only read by the render job.</p>

        @if($settings->hasVoice())
            <div class="mb-4">
                <audio controls preload="none" src="{{ route('admin.social.reels.settings.voice-preview') }}" style="width: 100%;"></audio>
                <p class="text-xs text-gray-400 mt-1">{{ $settings->voice_sample_name }} · uploaded {{ $settings->voice_uploaded_at?->format('M j, Y H:i') }}</p>
            </div>
        @endif

        <div class="text-sm text-gray-600 dark:text-gray-300 mb-4">
            <p class="font-medium mb-1">How to record</p>
            <ul class="list-disc pl-6 space-y-1">
                <li>1 to 2 minutes, in a quiet room. Your phone's voice recorder is fine.</li>
                <li>Hold the phone about a hand's width from your mouth.</li>
                <li>Talk the way you want your reels to sound: read a few paragraphs of one of your articles with real energy, as if explaining it to a friend on camera. A flat read gives a flat voice.</li>
                <li>The liveliest 10 seconds of the recording is what gets cloned.</li>
                <li>No music or background noise. Start talking within a second or two.</li>
            </ul>
        </div>

        <form method="POST" action="{{ route('admin.social.reels.settings.voice') }}" enctype="multipart/form-data" class="space-y-3"
            x-data="{ busy: false }" @submit="busy = true">
            @csrf
            <input type="file" name="voice" accept="audio/*,.m4a,.mp3,.wav" required
                class="block w-full text-sm text-gray-700 dark:text-gray-300">
            <button type="submit" class="btn-primary text-sm py-2 px-4" :disabled="busy"
                x-text="busy ? 'Uploading...' : '{{ $settings->hasVoice() ? 'Replace recording' : 'Upload recording' }}'">Upload recording</button>
        </form>
    </div>

    {{-- GitHub --}}
    <div class="admin-card p-6">
        <div class="flex items-center justify-between mb-1">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white">2. Connect GitHub</h2>
            <span class="text-xs px-2 py-1 rounded-full {{ $settings->hasGithub() ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }}">
                {{ $settings->hasGithub() ? 'Connected' : 'Not connected' }}
            </span>
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Videos are rendered for free on GitHub, in your <span class="font-mono">{{ $repo }}</span> repository. The site needs a token to start renders.</p>

        @if($settings->hasGithub())
            <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">
                Connected {{ $settings->render_token_synced_at?->format('M j, Y') }}.
                <a href="{{ $runsUrl }}" target="_blank" rel="noopener" class="text-blue-600 hover:underline">See render runs</a>.
                Paste a new token below only if this one expires.
            </p>
        @endif

        <ol class="list-decimal pl-6 space-y-1 text-sm text-gray-600 dark:text-gray-300 mb-4">
            <li>Open <a href="https://github.com/settings/personal-access-tokens/new" target="_blank" rel="noopener" class="text-blue-600 hover:underline">GitHub: new fine-grained token</a>.</li>
            <li>Name: <span class="font-mono">adilsher.pro reels</span>. Expiration: 1 year.</li>
            <li>Repository access: <strong>Only select repositories</strong>, then pick <span class="font-mono">{{ $repo }}</span>.</li>
            <li>Permissions, Repository permissions: <strong>Actions</strong> Read and write, <strong>Secrets</strong> Read and write.</li>
            <li>Click <strong>Generate token</strong>, copy it and paste it here.</li>
        </ol>

        <form method="POST" action="{{ route('admin.social.reels.settings.github') }}" class="space-y-3"
            x-data="{ busy: false }" @submit="busy = true">
            @csrf
            <input type="password" name="github_token" required autocomplete="off" placeholder="github_pat_..."
                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white font-mono text-sm">
            <button type="submit" class="btn-primary text-sm py-2 px-4" :disabled="busy"
                x-text="busy ? 'Connecting...' : 'Connect'">Connect</button>
            <p class="text-xs text-gray-400">The token is stored encrypted. The site checks it, then saves its own render key as a secret in your repository so the render job can send videos back.</p>
        </form>
    </div>

    {{-- Voice style + pronunciations --}}
    <form method="POST" action="{{ route('admin.social.reels.settings.voice-options') }}" class="admin-card p-6 lg:col-span-2">
        @csrf
        @method('PUT')
        <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-1">3. How your voice speaks</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Applies to the next render. Each scene is spoken up to three times and the clearest take is kept.</p>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div>
                <p class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Style</p>
                <div class="space-y-2">
                    @foreach(\App\Models\SocialStudioSettings::VOICE_STYLES as $key => $style)
                        <label class="flex items-start gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700 cursor-pointer">
                            <input type="radio" name="voice_style" value="{{ $key }}" class="mt-1" @checked(($settings->voice_style ?? 'natural') === $key)>
                            <span>
                                <span class="block font-medium text-gray-900 dark:text-white">{{ $style['label'] }}</span>
                                <span class="block text-sm text-gray-500 dark:text-gray-400">{{ $style['help'] }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>
            <div>
                <label for="pronunciations" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Pronunciations</label>
                <textarea id="pronunciations" name="pronunciations" rows="8"
                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white font-mono text-sm">{{ $settings->pronunciationText() }}</textarea>
                <p class="text-xs text-gray-400 mt-1">One per line: <span class="font-mono">word = how to say it</span>. Use this for any word the voice gets wrong or says differently in different places, for example <span class="font-mono">Laravel = Lara vel</span>. Only the voice changes; captions keep the real spelling.</p>
            </div>
        </div>

        <div class="flex justify-end mt-4">
            <button type="submit" class="btn-primary text-sm py-2 px-4">Save voice settings</button>
        </div>
    </form>
</div>
@endsection
