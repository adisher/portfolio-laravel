<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SocialDraft;
use App\Models\SocialStudioSettings;
use App\Services\Social\ReelDraftService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Endpoints the GitHub Actions render job calls (token-guarded): fetch what to
 * render, download the voice sample, then report the finished video or the
 * failure. Only drafts in the "rendering" state are served or accepted.
 */
class ReelRenderController extends Controller
{
    public function __construct(private ReelDraftService $reels) {}

    public function show(SocialDraft $draft)
    {
        abort_unless($draft->status === SocialDraft::STATUS_RENDERING, 409, 'Draft is not waiting for a render.');

        $post = $draft->blogPost;

        return response()->json([
            'id'     => $draft->id,
            'title'  => $post->title,
            'scenes' => $draft->scenes(),
            'brand'  => [
                'name'   => config('person.name'),
                'role'   => config('person.job_title'),
                'handle' => config('social.reels.handle'),
            ],
            'assets' => [
                'featured_image' => $this->absolute($post->featured_image && ! Str::endsWith($post->featured_image, '.svg')
                    ? (Str::startsWith($post->featured_image, ['http://', 'https://']) ? $post->featured_image : Storage::url($post->featured_image))
                    : null),
                'portrait'     => $this->absolute(asset(config('person.image'))),
                'voice_sample' => route('api.social.render.voice-sample'),
            ],
        ]);
    }

    public function voiceSample()
    {
        $path = SocialStudioSettings::instance()->voice_sample_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404, 'No voice sample uploaded.');

        return Storage::disk('local')->download($path);
    }

    /**
     * Receive the video in small pieces. Shared hosting often caps uploads at a
     * few MB, so the render job sends ~1.5 MB chunks in order and complete()
     * picks up the assembled file.
     */
    public function chunk(Request $request, SocialDraft $draft)
    {
        abort_unless($draft->status === SocialDraft::STATUS_RENDERING, 409, 'Draft is not waiting for a render.');

        $validated = $request->validate([
            'index'  => 'required|integer|min:0|max:500',
            'offset' => 'required|integer|min:0',
            'chunk'  => 'required|file|max:4096',
        ]);

        $path = $this->partPath($draft);
        Storage::disk('local')->makeDirectory(dirname($path));
        $full  = Storage::disk('local')->path($path);
        $bytes = file_get_contents($request->file('chunk')->getRealPath());

        // Chunk 0 starts a fresh file, so a new render never appends to an old one.
        if ((int) $validated['index'] === 0) {
            file_put_contents($full, $bytes);

            return response()->json(['ok' => true, 'bytes' => filesize($full)]);
        }

        clearstatcache(true, $full);
        $have = is_file($full) ? filesize($full) : 0;
        $offset = (int) $validated['offset'];

        if ($have === $offset) {
            file_put_contents($full, $bytes, FILE_APPEND);
        } elseif ($have !== $offset + strlen($bytes)) {
            // Not a harmless retry of the chunk we already have: the upload is out of order.
            abort(409, "Expected offset {$have}, got {$offset}.");
        }

        clearstatcache(true, $full);

        return response()->json(['ok' => true, 'bytes' => filesize($full)]);
    }

    public function complete(Request $request, SocialDraft $draft)
    {
        abort_unless($draft->status === SocialDraft::STATUS_RENDERING, 409, 'Draft is not waiting for a render.');

        $validated = $request->validate([
            'video'            => 'nullable|file|mimetypes:video/mp4|max:204800',
            'cover'            => 'required|file|mimes:jpg,jpeg,png|max:10240',
            'duration_seconds' => 'required|numeric|min:1|max:600',
            'scene_seconds'    => 'nullable|array',
            'scene_seconds.*'  => 'numeric|min:0|max:120',
        ]);

        $dir   = "social/reels/{$draft->id}";
        $stamp = now()->format('YmdHis');
        $old   = array_filter([$draft->video_path, $draft->cover_path]);
        $part  = $this->partPath($draft);

        if ($request->hasFile('video')) {
            $video = $request->file('video')->storeAs($dir, "reel-{$stamp}.mp4", 'public');
        } elseif (Storage::disk('local')->exists($part)) {
            $full = Storage::disk('local')->path($part);
            abort_unless((new \finfo(FILEINFO_MIME_TYPE))->file($full) === 'video/mp4', 422, 'Uploaded chunks are not an MP4 video.');
            abort_if(filesize($full) > 200 * 1024 * 1024, 422, 'Video is too large.');
            $video = "{$dir}/reel-{$stamp}.mp4";
            Storage::disk('public')->writeStream($video, fopen($full, 'rb'));
            Storage::disk('local')->delete($part);
        } else {
            abort(422, 'No video received.');
        }

        $cover = $request->file('cover')->storeAs($dir, "cover-{$stamp}." . $request->file('cover')->extension(), 'public');

        // Keep the script's timing in step with the real voiceover.
        $sceneSeconds = $validated['scene_seconds'] ?? [];
        if (count($sceneSeconds) === count($draft->scenes())) {
            $scenes = $draft->scenes();
            foreach ($scenes as $i => $scene) {
                $scenes[$i]['duration_seconds'] = round((float) $sceneSeconds[$i], 1);
            }
            $draft->update(['script' => ['scenes' => $scenes]]);
        }

        $this->reels->markRendered($draft, $video, $cover, (int) round($validated['duration_seconds']));
        Storage::disk('public')->delete($old);

        return response()->json(['ok' => true, 'review_url' => route('admin.social.reels.show', $draft)]);
    }

    public function failed(Request $request, SocialDraft $draft)
    {
        $validated = $request->validate(['error' => 'required|string|max:4000']);

        if ($draft->status === SocialDraft::STATUS_RENDERING) {
            $this->reels->markRenderFailed($draft, $validated['error']);
        }

        return response()->json(['ok' => true]);
    }

    private function partPath(SocialDraft $draft): string
    {
        return "social/render-uploads/{$draft->id}/reel.mp4.part";
    }

    private function absolute(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        return Str::startsWith($url, ['http://', 'https://'])
            ? $url
            : rtrim(config('app.url'), '/') . '/' . ltrim($url, '/');
    }
}
