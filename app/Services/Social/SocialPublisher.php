<?php

namespace App\Services\Social;

use App\Models\BlogPost;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Services\Social\Contracts\SocialDriver;
use App\Services\Social\Drivers\FacebookDriver;
use App\Services\Social\Drivers\LinkedInDriver;
use App\Support\Text;
use Illuminate\Support\Str;

/**
 * Central entry point for the in-project social flow. Owns the driver registry,
 * composes captions from an account's template, delegates the actual API call
 * to the platform driver, and records the outcome in social_posts.
 *
 * To add a platform: implement SocialDriver and add it to DRIVERS below.
 */
class SocialPublisher
{
    /** platform key => driver class. */
    private const DRIVERS = [
        'facebook' => FacebookDriver::class,
        'linkedin' => LinkedInDriver::class,
    ];

    /** @return array<string, SocialDriver> keyed by platform. */
    public function drivers(): array
    {
        return collect(self::DRIVERS)
            ->mapWithKeys(fn ($class, $key) => [$key => app($class)])
            ->all();
    }

    public function driverFor(SocialAccount $account): ?SocialDriver
    {
        $class = self::DRIVERS[$account->platform] ?? null;

        return $class ? app($class) : null;
    }

    public function supportsPlatform(string $platform): bool
    {
        return array_key_exists($platform, self::DRIVERS);
    }

    /**
     * Publish one article to one account. Idempotent per (article, account):
     * an article already posted there is skipped; a prior failure is retried.
     * Returns the SocialPost log row (status posted|failed|skipped).
     */
    public function publish(SocialAccount $account, BlogPost $post, string $trigger = 'manual'): SocialPost
    {
        $existing = SocialPost::where('blog_post_id', $post->id)
            ->where('social_account_id', $account->id)
            ->first();

        if ($existing && $existing->status === 'posted') {
            return $existing; // already live, never post twice
        }

        $driver = $this->driverFor($account);

        if (! $driver) {
            return $this->record($account, $post, $trigger, PublishResult::fail(
                "No driver registered for platform '{$account->platform}'."
            ), null, $existing);
        }

        $message = $this->composeCaption($account, $post);
        $result  = $driver->publish($account, $post, $message);

        return $this->record($account, $post, $trigger, $result, $message, $existing);
    }

    /**
     * Render an account's caption template for an article. Supported tokens:
     * {title} {excerpt} {url} {category} {hashtags}
     */
    public function composeCaption(SocialAccount $account, BlogPost $post): string
    {
        $post->loadMissing(['category', 'tags']);

        $replacements = [
            '{title}'    => $post->title,
            '{excerpt}'  => $post->excerpt,
            '{url}'      => \App\Support\SocialUrl::for($post, $account->platform),
            '{category}' => optional($post->category)->name ?? '',
            '{hashtags}' => $this->hashtags($post),
        ];

        $caption = strtr($account->captionTemplateOrDefault(), $replacements);

        // Enforce house style (no em dashes) then collapse blank lines and trim.
        $caption = Text::stripEmDashes($caption);

        return trim(preg_replace("/\n{3,}/", "\n\n", $caption));
    }

    /** Turn tag names into space-joined #CamelCase hashtags (max 6). */
    public function hashtags(BlogPost $post): string
    {
        return $post->tags
            ->take(6)
            ->map(fn ($tag) => '#' . Str::studly(preg_replace('/[^A-Za-z0-9 ]/', '', $tag->name)))
            ->implode(' ');
    }

    private function record(
        SocialAccount $account,
        BlogPost $post,
        string $trigger,
        PublishResult $result,
        ?string $message,
        ?SocialPost $existing
    ): SocialPost {
        $attributes = [
            'platform'     => $account->platform,
            'status'       => $result->success ? 'posted' : 'failed',
            'trigger'      => $trigger,
            'message'      => $message,
            'external_id'  => $result->externalId,
            'external_url' => $result->externalUrl,
            'error'        => $result->error,
            'posted_at'    => $result->success ? now() : null,
        ];

        $log = $existing
            ? tap($existing)->update($attributes)
            : SocialPost::create(array_merge($attributes, [
                'blog_post_id'      => $post->id,
                'social_account_id' => $account->id,
            ]));

        if ($result->success) {
            $account->forceFill(['last_posted_at' => now()])->save();
        }

        return $log;
    }
}
