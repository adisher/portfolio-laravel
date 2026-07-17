{{-- Expects: $v (WorkItemVoice) --}}
<div class="border border-gray-200 dark:border-gray-700 rounded-lg p-3">
    <div class="flex gap-3">
        <div class="flex-shrink-0 w-16">
            @if($v->media)
            <a href="{{ $v->media->url }}" target="_blank">
                <img src="{{ $v->media->url }}" alt="" class="w-16 h-16 object-cover rounded border border-gray-200 dark:border-gray-600">
            </a>
            <form method="POST" action="{{ route('admin.work-items.voices.update', $v) }}" class="text-center mt-1">
                @csrf @method('PATCH')
                <input type="hidden" name="remove_screenshot" value="1">
                <button class="text-[11px] text-red-500 hover:underline">remove</button>
            </form>
            @else
            <div class="w-16 h-16 rounded bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-gray-300 text-[10px] text-center">no shot</div>
            @endif
        </div>
        <div class="flex-1 min-w-0">
            <p class="text-sm text-gray-800 dark:text-gray-200">{{ $v->quote }}</p>
            <p class="text-xs text-gray-400 mt-1">
                @if($v->attribution){{ $v->attribution }}@endif
                @if($v->source_url) &middot; <a href="{{ $v->source_url }}" target="_blank" class="text-teal hover:underline">visit source</a>@endif
                @if($v->meta['note'] ?? null) &middot; {{ $v->meta['note'] }}@endif
            </p>
            <div class="flex flex-wrap items-center gap-x-4 gap-y-2 mt-2">
                <form method="POST" action="{{ route('admin.work-items.voices.update', $v) }}">
                    @csrf @method('PATCH')
                    <input type="hidden" name="status" value="{{ $v->status === 'approved' ? 'candidate' : 'approved' }}">
                    <button class="text-xs {{ $v->status === 'approved' ? 'text-gray-500' : 'text-green-600' }} hover:underline">
                        {{ $v->status === 'approved' ? 'Unapprove' : 'Approve' }}
                    </button>
                </form>

                {{-- Screenshot the source, copy the image, then paste it here --}}
                <form method="POST" action="{{ route('admin.work-items.voices.update', $v) }}" enctype="multipart/form-data" class="voice-upload flex items-center gap-2" data-voice="{{ $v->id }}">
                    @csrf @method('PATCH')
                    <input type="file" name="screenshot" accept="image/*" class="voice-file hidden">
                    <button type="button" class="voice-paste text-xs text-teal hover:underline whitespace-nowrap">{{ $v->media ? 'Paste to replace' : 'Paste screenshot' }}</button>
                    <img class="voice-preview hidden w-8 h-8 object-cover rounded border border-gray-300 dark:border-gray-600" alt="">
                    <button type="submit" class="voice-save hidden text-xs font-medium text-green-600 hover:underline">Save</button>
                    <button type="button" class="voice-choose text-[11px] text-gray-400 hover:underline whitespace-nowrap">or choose file</button>
                </form>

                <form method="POST" action="{{ route('admin.work-items.voices.destroy', $v) }}" onsubmit="return confirm('Remove this voice?')">
                    @csrf @method('DELETE')
                    <button class="text-xs text-red-500 hover:underline">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>
