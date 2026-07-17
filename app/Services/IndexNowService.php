<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class IndexNowService
{
    protected string $apiUrl = 'https://api.indexnow.org/indexnow';
    protected ?string $apiKey;
    protected string $host;

    public function __construct()
    {
        $this->apiKey = config('services.indexnow.key');
        $this->host = parse_url(config('app.url'), PHP_URL_HOST) ?? 'localhost';
    }

    /**
     * Submit a URL for indexing.
     */
    public function submit(string $url): bool
    {
        if (!$this->isConfigured()) {
            Log::info('IndexNow not configured, skipping submission');
            return false;
        }

        try {
            $response = Http::timeout(10)->get($this->apiUrl, [
                'url' => $url,
                'key' => $this->apiKey,
            ]);

            $ok = $response->successful() || $response->status() === 200 || $response->status() === 202;
            \App\Models\ToolUsageLog::record('indexnow', 'submit', 1, 'urls', $ok, null, ['status' => $response->status()]);

            if ($ok) {
                Log::info("IndexNow: Successfully submitted {$url}");
                return true;
            }

            Log::warning("IndexNow: Failed to submit {$url}, status: {$response->status()}");
            return false;

        } catch (\Exception $e) {
            Log::error("IndexNow: Exception submitting {$url}: " . $e->getMessage());
            \App\Models\ToolUsageLog::record('indexnow', 'submit', 1, 'urls', false, null, ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Submit multiple URLs for indexing.
     */
    public function submitBatch(array $urls): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'message' => 'IndexNow not configured'];
        }

        if (empty($urls)) {
            return ['success' => true, 'submitted' => 0];
        }

        try {
            $response = Http::timeout(30)->post($this->apiUrl, [
                'host' => $this->host,
                'key' => $this->apiKey,
                'keyLocation' => url($this->apiKey . '.txt'),
                'urlList' => array_slice($urls, 0, 10000), // Max 10,000 URLs per request
            ]);

            $ok = $response->successful() || $response->status() === 200 || $response->status() === 202;
            \App\Models\ToolUsageLog::record('indexnow', 'submit_batch', count($urls), 'urls', $ok, null, ['status' => $response->status()]);

            if ($ok) {
                Log::info("IndexNow: Successfully submitted " . count($urls) . " URLs");
                return [
                    'success' => true,
                    'submitted' => count($urls),
                ];
            }

            Log::warning("IndexNow: Batch submission failed, status: {$response->status()}");
            return [
                'success' => false,
                'message' => 'Submission failed',
                'status' => $response->status(),
            ];

        } catch (\Exception $e) {
            Log::error("IndexNow: Batch submission exception: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check if IndexNow is configured.
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Generate and save the API key verification file.
     */
    public function generateKeyFile(): bool
    {
        if (!$this->apiKey) {
            return false;
        }

        try {
            $filePath = public_path($this->apiKey . '.txt');
            file_put_contents($filePath, $this->apiKey);

            Log::info("IndexNow: Key file generated at {$filePath}");
            return true;

        } catch (\Exception $e) {
            Log::error("IndexNow: Failed to generate key file: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get the key file URL for verification.
     */
    public function getKeyFileUrl(): ?string
    {
        if (!$this->apiKey) {
            return null;
        }

        return url($this->apiKey . '.txt');
    }

    /**
     * Submit sitemap URL (alternative method).
     */
    public function submitSitemap(): bool
    {
        $sitemapUrl = url('/sitemap.xml');
        return $this->submit($sitemapUrl);
    }

    /**
     * Generate a new API key if not set.
     */
    public static function generateApiKey(): string
    {
        return bin2hex(random_bytes(16));
    }
}
