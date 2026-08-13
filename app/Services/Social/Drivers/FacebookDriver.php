<?php

namespace App\Services\Social\Drivers;

use App\Models\BlogPost;
use App\Models\SocialAccount;
use App\Services\Social\Contracts\SocialDriver;
use App\Services\Social\PublishResult;
use Illuminate\Support\Facades\Http;

/**
 * Publishes a link post to a Facebook Page feed via the Graph API:
 *   POST /{page_id}/feed  { message, link, access_token }
 *
 * Facebook builds the image/headline card from the article page's Open Graph
 * tags, so we send the canonical URL as `link` and never upload the image.
 * Requires a Page access token with pages_manage_posts + pages_read_engagement.
 */
class FacebookDriver implements SocialDriver
{
    private const GRAPH_VERSION = 'v21.0';

    public function key(): string
    {
        return 'facebook';
    }

    public function label(): string
    {
        return 'Facebook Page';
    }

    public function credentialFields(): array
    {
        return [
            'page_id'      => 'Page ID',
            'access_token' => 'Page Access Token',
        ];
    }

    public function publish(SocialAccount $account, BlogPost $post, string $message): PublishResult
    {
        $pageId = $account->credential('page_id');
        $token  = $account->credential('access_token');

        if (empty($pageId) || empty($token)) {
            return PublishResult::fail('Facebook account is missing a Page ID or Access Token.');
        }

        try {
            $response = Http::asForm()
                ->timeout(20)
                ->post("https://graph.facebook.com/" . self::GRAPH_VERSION . "/{$pageId}/feed", [
                    'message'      => $message,
                    'link'         => route('blog.show', $post->slug),
                    'access_token' => $token,
                ]);
        } catch (\Throwable $e) {
            return PublishResult::fail('Could not reach Facebook: ' . $e->getMessage());
        }

        if ($response->failed()) {
            $error = $response->json('error.message') ?? ('HTTP ' . $response->status());
            return PublishResult::fail($error);
        }

        $id = $response->json('id');

        return PublishResult::ok(
            externalId: $id,
            externalUrl: $id ? "https://www.facebook.com/{$id}" : null,
        );
    }
}
