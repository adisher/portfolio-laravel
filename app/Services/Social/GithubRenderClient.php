<?php

namespace App\Services\Social;

use App\Models\SocialStudioSettings;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Talks to GitHub for the reel render pipeline: checks the owner's token,
 * stores the render callback token as an Actions secret, and starts the
 * render workflow. The token needs, on this repository only:
 * Actions read/write, Secrets read/write (Metadata read is implied).
 */
class GithubRenderClient
{
    private const API = 'https://api.github.com';

    /** Confirm the token can see the repo and manage its Actions secrets. */
    public function verify(string $token): void
    {
        $this->check($this->request($token)->get($this->repoPath()), 'reach the repository');
        $this->check($this->request($token)->get($this->repoPath('/actions/secrets/public-key')), 'read Actions secrets');
    }

    /**
     * Save the render token as the repository's SOCIAL_RENDER_TOKEN secret,
     * sealed with the repo's public key (GitHub only accepts encrypted values).
     */
    public function syncRenderToken(SocialStudioSettings $settings): void
    {
        $token = (string) $settings->github_token;
        $key = $this->check(
            $this->request($token)->get($this->repoPath('/actions/secrets/public-key')),
            'read the Actions public key'
        );

        $sealed = sodium_crypto_box_seal((string) $settings->render_token, base64_decode($key->json('key')));

        $this->check(
            $this->request($token)->put($this->repoPath('/actions/secrets/' . config('social.render.secret_name')), [
                'encrypted_value' => base64_encode($sealed),
                'key_id'          => $key->json('key_id'),
            ]),
            'save the render secret'
        );

        $settings->forceFill(['render_token_synced_at' => now()])->save();
    }

    /** Start the render workflow for one draft. */
    public function dispatchRender(int $draftId): void
    {
        $settings = SocialStudioSettings::instance();

        $this->check(
            $this->request((string) $settings->github_token)->post(
                $this->repoPath('/actions/workflows/' . config('social.render.workflow') . '/dispatches'),
                ['ref' => config('social.render.ref'), 'inputs' => ['draft_id' => (string) $draftId]]
            ),
            'start the render workflow'
        );
    }

    public function runsUrl(): string
    {
        return 'https://github.com/' . config('social.render.repo') . '/actions/workflows/' . config('social.render.workflow');
    }

    private function request(string $token): PendingRequest
    {
        return Http::withToken($token)
            ->withHeaders([
                'Accept'               => 'application/vnd.github+json',
                'X-GitHub-Api-Version' => '2022-11-28',
            ])
            ->timeout(20);
    }

    private function repoPath(string $suffix = ''): string
    {
        return self::API . '/repos/' . config('social.render.repo') . $suffix;
    }

    private function check(Response $response, string $action): Response
    {
        if ($response->successful()) {
            return $response;
        }

        $hint = match ($response->status()) {
            401     => 'the token is invalid or expired',
            403     => 'the token is missing a permission (needs Actions and Secrets: read and write on this repository)',
            404     => 'the token cannot see this repository or the workflow file is missing',
            422     => 'GitHub rejected the request: ' . ($response->json('message') ?? 'unprocessable'),
            default => 'GitHub returned HTTP ' . $response->status(),
        };

        throw new RuntimeException("Could not {$action}: {$hint}.");
    }
}
