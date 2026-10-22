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
        'voice_style', 'pronunciations',
    ];

    /**
     * Chatterbox settings per style. Exaggeration drives emotion and pitch
     * movement; a lower cfg_weight lets the delivery stray further from a flat
     * read; temperature is sampling randomness, kept moderate so words stay
     * consistent between scenes.
     */
    public const VOICE_STYLES = [
        // Natural started at the first render's 0.5/0.5/0.7 and was lifted a
        // touch for energy. Above ~0.75 exaggeration Chatterbox rushes and adds
        // non-word sounds (tried 0.85). Lower cfg_weight slows delivery,
        // offsetting exaggeration's speed-up.
        // Temperature 0.8 is Chatterbox's own default; lower flattens the
        // intonation and sounds robotic. Takes are checked word by word, so
        // the extra variation cannot slip in wrong words.
        // `lift` is used for the hook and end lines only, so the opening and
        // the sign-off sound like an intro and an outro, not more narration.
        'calm'       => ['label' => 'Calm', 'help' => 'Even and steady, like a narration.', 'exaggeration' => 0.42, 'cfg_weight' => 0.55, 'temperature' => 0.75, 'lift' => ['exaggeration' => 0.55, 'cfg_weight' => 0.5]],
        'natural'    => ['label' => 'Natural', 'help' => 'Conversational, closest to your own voice.', 'exaggeration' => 0.55, 'cfg_weight' => 0.5, 'temperature' => 0.8, 'lift' => ['exaggeration' => 0.7, 'cfg_weight' => 0.45]],
        'expressive' => ['label' => 'Expressive', 'help' => 'A little more energy and emphasis.', 'exaggeration' => 0.62, 'cfg_weight' => 0.45, 'temperature' => 0.8, 'lift' => ['exaggeration' => 0.75, 'cfg_weight' => 0.4]],
    ];

    /** Words the voice should always say a particular way. Editable in the admin. */
    public const DEFAULT_PRONUNCIATIONS = "adilsher.pro = Adil Sher dot pro\nnginx = engine x\nkubectl = cube control\ncache = cash";

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

    /** @return array{exaggeration: float, cfg_weight: float, temperature: float} */
    public function voiceParams(): array
    {
        $style = static::VOICE_STYLES[$this->voice_style] ?? static::VOICE_STYLES['natural'];

        return array_intersect_key($style, array_flip(['exaggeration', 'cfg_weight', 'temperature', 'lift']));
    }

    public function pronunciationText(): string
    {
        return $this->pronunciations ?? static::DEFAULT_PRONUNCIATIONS;
    }

    /**
     * Parse "word = how to say it" lines (":" also works).
     *
     * @return array<int, array{0: string, 1: string}>
     */
    public function pronunciationList(): array
    {
        $list = [];
        foreach (preg_split('/\R/', $this->pronunciationText()) as $line) {
            if (preg_match('/^\s*([^=:]+?)\s*[=:]\s*(.+?)\s*$/u', $line, $m)) {
                $list[] = [$m[1], $m[2]];
            }
        }

        return $list;
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
