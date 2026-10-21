<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SocialStudioSettings;
use App\Services\Social\GithubRenderClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Studio settings for rendering reels: the owner's voice sample (kept on the
 * private disk, never public) and the GitHub connection that runs renders.
 */
class SocialStudioController extends Controller
{
    public function __construct(private GithubRenderClient $github) {}

    public function edit()
    {
        $settings = SocialStudioSettings::instance();

        return view('admin.social.reels.settings', [
            'settings' => $settings,
            'runsUrl'  => $this->github->runsUrl(),
            'repo'     => config('social.render.repo'),
        ]);
    }

    public function uploadVoice(Request $request)
    {
        $request->validate([
            'voice' => 'required|file|max:51200|mimes:mp3,m4a,wav,aac,ogg,oga,webm,mp4,mpga',
        ], [
            'voice.mimes' => 'Upload an audio file (mp3, m4a, wav, aac, ogg or webm).',
        ]);

        $settings = SocialStudioSettings::instance();
        $file     = $request->file('voice');
        $path     = $file->storeAs('social/voice', 'sample-' . now()->format('YmdHis') . '.' . $file->extension(), 'local');

        if ($settings->voice_sample_path && $settings->voice_sample_path !== $path) {
            Storage::disk('local')->delete($settings->voice_sample_path);
        }

        $settings->forceFill([
            'voice_sample_path' => $path,
            'voice_sample_name' => $file->getClientOriginalName(),
            'voice_uploaded_at' => now(),
        ])->save();

        return back()->with('success', 'Voice recording saved. Every reel will use it from now on.');
    }

    public function voicePreview()
    {
        $path = SocialStudioSettings::instance()->voice_sample_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }

    public function connectGithub(Request $request)
    {
        $validated = $request->validate(['github_token' => 'required|string|min:20|max:255']);
        $token     = trim($validated['github_token']);

        try {
            $this->github->verify($token);

            $settings = SocialStudioSettings::instance();
            $settings->forceFill(['github_token' => $token])->save();
            $settings->rotateRenderToken();
            $this->github->syncRenderToken($settings->fresh());
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'GitHub connected. Renders can start from any reel.');
    }
}
