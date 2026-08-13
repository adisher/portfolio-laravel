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

    /** The credential field names this driver expects (for the account form). */
    public function credentialFields(): array;

    /**
     * Publish an already-composed caption for $post to $account.
     * Must not throw for expected API failures: return PublishResult::fail().
     */
    public function publish(SocialAccount $account, BlogPost $post, string $message): PublishResult;
}
