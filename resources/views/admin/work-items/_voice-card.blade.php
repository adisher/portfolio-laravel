{{-- Expects: $v (WorkItemVoice), $mediaOptions --}}
<div class="border border-gray-200 dark:border-gray-700 rounded-lg p-3">
    <div class="flex gap-3">
        <div class="flex-shrink-0">
            @if($v->media)
            <a href="{{ $v->media->url }}" target="_blank">
                <img src="{{ $v->media->url }}" alt="" class="w-16 h-16 object-cover rounded border border-gray-200 dark:border-gray-600">
            </a>
            @else
            <div class="w-16 h-16 rounded bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-gray-300 text-[10px] text-center">no shot</div>
            @endif
        </div>
        <div class="flex-1 min-w-0">
            <p class="text-sm text-gray-800 dark:text-gray-200">{{ $v->quote }}</p>
            <p class="text-xs text-gray-400 mt-1">
                @if($v->attribution){{ $v->attribution }}@endif
                @if($v->source_url) &middot; <a href="{{ $v->source_url }}" target="_blank" class="text-teal hover:underline">source</a>@endif
                @if($v->meta['note'] ?? null) &middot; {{ $v->meta['note'] }}@endif
            </p>
            <div class="flex flex-wrap items-center gap-3 mt-2">
                <form method="POST" action="{{ route('admin.work-items.voices.update', $v) }}">
                    @csrf @method('PATCH')
                    <input type="hidden" name="status" value="{{ $v->status === 'approved' ? 'candidate' : 'approved' }}">
                    <button class="text-xs {{ $v->status === 'approved' ? 'text-gray-500' : 'text-green-600' }} hover:underline">
                        {{ $v->status === 'approved' ? 'Unapprove' : 'Approve' }}
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.work-items.voices.update', $v) }}" class="flex items-center gap-1">
                    @csrf @method('PATCH')
                    <select name="media_id" class="text-xs rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 max-w-[9rem]">
                        <option value="">— screenshot —</option>
                        @foreach($mediaOptions as $mo)
                        <option value="{{ $mo->id }}" {{ $v->media_id == $mo->id ? 'selected' : '' }}>{{ \Illuminate\Support\Str::limit($mo->file_name, 20) }}</option>
                        @endforeach
                    </select>
                    <button class="text-xs text-teal hover:underline">Save</button>
                </form>
                <form method="POST" action="{{ route('admin.work-items.voices.destroy', $v) }}" onsubmit="return confirm('Remove this voice?')">
                    @csrf @method('DELETE')
                    <button class="text-xs text-red-500 hover:underline">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>
