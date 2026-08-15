<?php

namespace App\Services\Social\Drivers;

use App\Models\BlogPost;
use App\Models\SocialAccount;
use App\Services\Social\Contracts\SocialDriver;
use App\Services\Social\PublishResult;
use App\Support\SocialUrl;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Publishes an article share to a LinkedIn member's feed via the UGC Posts API:
 *   POST /v2/ugcPosts  (ShareContent, shareMediaCategory ARTICLE)
 *
 * LinkedIn renders its own preview card from the article URL's Open Graph tags,
 * so we send the canonical URL plus title/description and never upload media.
 *
 * Requires an OAuth 2.0 token with the `w_member_social` scope. The author URN
 * (urn:li:person:{id}) is auto-detected from the token via /v2/userinfo when
 * not supplied.
 */
class LinkedInDriver implements SocialDriver
{
    private const API = 'https://api.linkedin.com';

    public function key(): string
    {
        return 'linkedin';
    }

    public function label(): string
    {
        return 'LinkedIn';
    }

    public function allowsAutomatedPosting(): bool
    {
        // LinkedIn's API terms prohibit automated posting for self-serve apps.
        return false;
    }

    public function tokenLifetimeDays(): ?int
    {
        return 60; // LinkedIn member access tokens last ~60 days
    }

    public function credentialFields(): array
    {
        return [
            'access_token' => [
                'label'    => 'Access Token',
                'type'     => 'password',
                'required' => true,
                'help'     => 'OAuth 2.0 token with the w_member_social scope. Stored encrypted. LinkedIn tokens expire in ~60 days.',
            ],
            'author_urn' => [
                'label'    => 'Author URN (optional)',
                'type'     => 'text',
                'required' => false,
                'help'     => 'e.g. urn:li:person:AbC123. Leave blank to auto-detect from the token.',
            ],
        ];
    }

    public function publish(SocialAccount $account, BlogPost $post, string $message): PublishResult
    {
        $token = $account->credential('access_token');

        if (empty($token)) {
            return PublishResult::fail('LinkedIn account is missing an access token.');
        }

        $author = $account->credential('author_urn') ?: $this->resolveAuthorUrn($token);

        if (empty($author)) {
            return PublishResult::fail('Could not resolve the LinkedIn author URN from the token. Add it manually or grant the profile scope.');
        }

        $payload = [
            'author'          => $author,
            'lifecycleState'  => 'PUBLISHED',
            'specificContent' => [
                'com.linkedin.ugc.ShareContent' => [
                    'shareCommentary'    => ['text' => $message],
                    'shareMediaCategory' => 'ARTICLE',
                    'media'              => [[
                        'status'      => 'READY',
                        'originalUrl' => SocialUrl::for($post, $this->key()),
                        'title'       => ['text' => Str::limit($post->title, 190, '')],
                        'description' => ['text' => Str::limit((string) $post->excerpt, 250, '')],
                    ]],
                ],
            ],
            'visibility' => [
                'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC',
            ],
        ];

        try {
            $response = Http::withToken($token)
                ->withHeaders(['X-Restli-Protocol-Version' => '2.0.0'])
                ->timeout(20)
                ->post(self::API . '/v2/ugcPosts', $payload);
        } catch (\Throwable $e) {
            return PublishResult::fail('Could not reach LinkedIn: ' . $e->getMessage());
        }

        if ($response->failed()) {
            $error = $response->json('message') ?? ('HTTP ' . $response->status());
            return PublishResult::fail($error);
        }

        $id = $response->header('x-restli-id') ?: $response->json('id');

        return PublishResult::ok(
            externalId: $id,
            externalUrl: $id ? "https://www.linkedin.com/feed/update/{$id}" : null,
        );
    }

    /** Resolve urn:li:person:{id} from the token via OpenID userinfo, then /v2/me. */
    private function resolveAuthorUrn(string $token): ?string
    {
        try {
            $info = Http::withToken($token)->timeout(15)->get(self::API . '/v2/userinfo');
            if ($info->ok() && $info->json('sub')) {
                return 'urn:li:person:' . $info->json('sub');
            }

            $me = Http::withToken($token)
                ->withHeaders(['X-Restli-Protocol-Version' => '2.0.0'])
                ->timeout(15)
                ->get(self::API . '/v2/me');
            if ($me->ok() && $me->json('id')) {
                return 'urn:li:person:' . $me->json('id');
            }
        } catch (\Throwable $e) {
            // fall through to null
        }

        return null;
    }
}
