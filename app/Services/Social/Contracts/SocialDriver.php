<?php

namespace App\Services\Social\Contracts;

use App\Models\BlogPost;
use App\Models\SocialAccount;
use App\Services\Social\PublishResult;

/**
 * A platform adapter. To add X / LinkedIn / Instagram later, implement this
 * interface and register the class in SocialPublisher::DRIVERS. Nothing else
 * in the pipeline needs to change.
 */
interface SocialDriver
{
    /** Machine key, e.g. "facebook". Must match SocialAccount::$platform. */
    public function key(): string;

    /** Human label for the admin UI, e.g. "Facebook Page". */
    public function label(): string;

    /**
     * Whether this platform permits automated/scheduled posting under its API
     * terms. LinkedIn's API terms forbid automated posting for self-serve apps,
     * so its driver returns false and the social:publish scheduler skips it;
     * manual "Post now" remains available.
     */
    public function allowsAutomatedPosting(): bool;

    /**
     * The credential fields this driver needs, keyed by storage key. Each value
     * is metadata the account form renders generically:
     *   ['label' => string, 'type' => 'text'|'password', 'required' => bool, 'help' => string]
     */
    public function credentialFields(): array;

    /**
     * Publish an already-composed caption for $post to $account.
     * Must not throw for expected API failures: return PublishResult::fail().
     */
    public function publish(SocialAccount $account, BlogPost $post, string $message): PublishResult;
}
