<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Single-row settings for rendering reels: the owner's voice sample and the
 * GitHub connection. Tokens are encrypted at rest and never serialised.
 */
class SocialStudioSettings extends Model
{
    protected $table = 'social_studio_settings';

    protected $fillable = [
        'github_token', 'render_token', 'render_token_synced_at',
        'voice_sample_path', 'voice_sample_name', 'voice_uploaded_at',
    ];

    protected $casts = [
        'github_token'           => 'encrypted',
        'render_token'           => 'encrypted',
        'render_token_synced_at' => 'datetime',
        'voice_uploaded_at'      => 'datetime',
    ];

    protected $hidden = ['github_token', 'render_token'];

    public static function instance(): self
    {
        return static::firstOrCreate([]);
    }

    public function hasVoice(): bool
    {
        return ! empty($this->voice_sample_path);
    }

    public function hasGithub(): bool
    {
        return ! empty($this->github_token) && $this->render_token_synced_at !== null;
    }

    public function readyToRender(): bool
    {
        return $this->hasVoice() && $this->hasGithub();
    }

    /** Make (or replace) the token the render workflow uses to call back. */
    public function rotateRenderToken(): string
    {
        $token = Str::random(64);
        $this->forceFill(['render_token' => $token, 'render_token_synced_at' => null])->save();

        return $token;
    }
}
