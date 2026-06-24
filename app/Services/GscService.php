<?php

namespace App\Services;

use Google\Client;
use Google\Service\SearchConsole;
use Google\Service\SearchConsole\SearchAnalyticsQueryRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GscService
{
    protected ?string $siteUrl;
    protected ?string $credentialsPath;

    public function __construct()
    {
        $this->siteUrl = config('services.gsc.site_url');

        $cred = config('services.gsc.credentials');
        if ($cred) {
            // Accept absolute paths as-is, otherwise resolve relative to the app root
            $this->credentialsPath = (str_starts_with($cred, '/') || preg_match('/^[A-Za-z]:/', $cred))
                ? $cred
                : base_path($cred);
        } else {
            $this->credentialsPath = null;
        }
    }

    public function isConfigured(): bool
    {
        return !empty($this->siteUrl)
            && !empty($this->credentialsPath)
            && file_exists($this->credentialsPath);
    }

    protected function service(): SearchConsole
    {
        $client = new Client();
        $client->setAuthConfig($this->credentialsPath);
        $client->addScope('https://www.googleapis.com/auth/webmasters.readonly');
        return new SearchConsole($client);
    }

    /**
     * GSC data lags ~2 days; anchor ranges to (today - 2) as the freshest date.
     */
    protected function range(int $days): array
    {
        return [
            now()->subDays(2 + $days)->toDateString(),
            now()->subDays(2)->toDateString(),
        ];
    }

    /**
     * Run a searchanalytics query and return normalized rows.
     */
    protected function query(string $start, string $end, array $dimensions = [], int $rowLimit = 1000): array
    {
        if (!$this->isConfigured()) {
            return [];
        }

        try {
            $req = new SearchAnalyticsQueryRequest();
            $req->setStartDate($start);
            $req->setEndDate($end);
            if (!empty($dimensions)) {
                $req->setDimensions($dimensions);
            }
            $req->setRowLimit($rowLimit);

            $resp = $this->service()->searchanalytics->query($this->siteUrl, $req);

            $rows = [];
            foreach ($resp->getRows() ?? [] as $row) {
                $rows[] = [
                    'keys'        => $row->getKeys() ?? [],
                    'clicks'      => (int) $row->getClicks(),
                    'impressions' => (int) $row->getImpressions(),
                    'ctr'         => (float) $row->getCtr(),
                    'position'    => (float) $row->getPosition(),
                ];
            }
            return $rows;

        } catch (\Throwable $e) {
            Log::warning('GSC query failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Period totals with week-over-week deltas (no dimensions = one aggregate row).
     */
    public function summary(int $days = 28): array
    {
        return Cache::remember("gsc:summary:{$days}", now()->addHours(6), function () use ($days) {
            [$start, $end] = $this->range($days);
            $prevStart = now()->subDays(2 + $days * 2)->toDateString();
            $prevEnd   = now()->subDays(3 + $days)->toDateString();

            $cur  = $this->query($start, $end)[0]  ?? ['clicks' => 0, 'impressions' => 0, 'ctr' => 0, 'position' => 0];
            $prev = $this->query($prevStart, $prevEnd)[0] ?? ['clicks' => 0, 'impressions' => 0, 'ctr' => 0, 'position' => 0];

            $pct = fn($c, $p) => $p > 0 ? round((($c - $p) / $p) * 100, 1) : ($c > 0 ? 100 : 0);

            return [
                'current'  => $cur,
                'previous' => $prev,
                'delta'    => [
                    'clicks'      => $pct($cur['clicks'], $prev['clicks']),
                    'impressions' => $pct($cur['impressions'], $prev['impressions']),
                    // Position: lower is better, so report the raw change (negative = improved)
                    'position'    => round($cur['position'] - $prev['position'], 1),
                ],
            ];
        });
    }

    public function trend(int $days = 28): array
    {
        return Cache::remember("gsc:trend:{$days}", now()->addHours(6), function () use ($days) {
            [$start, $end] = $this->range($days);
            return collect($this->query($start, $end, ['date']))
                ->map(fn($r) => [
                    'date'        => $r['keys'][0] ?? '',
                    'clicks'      => $r['clicks'],
                    'impressions' => $r['impressions'],
                ])->all();
        });
    }

    public function topQueries(int $days = 28, int $limit = 15): array
    {
        return Cache::remember("gsc:queries:{$days}:{$limit}", now()->addHours(6), function () use ($days, $limit) {
            [$start, $end] = $this->range($days);
            return collect($this->query($start, $end, ['query'], $limit))
                ->map(fn($r) => [
                    'query'       => $r['keys'][0] ?? '',
                    'clicks'      => $r['clicks'],
                    'impressions' => $r['impressions'],
                    'ctr'         => $r['ctr'],
                    'position'    => $r['position'],
                ])->all();
        });
    }

    public function topPages(int $days = 28, int $limit = 15): array
    {
        return Cache::remember("gsc:pages:{$days}:{$limit}", now()->addHours(6), function () use ($days, $limit) {
            [$start, $end] = $this->range($days);
            return collect($this->query($start, $end, ['page'], $limit))
                ->map(fn($r) => [
                    'page'        => $r['keys'][0] ?? '',
                    'clicks'      => $r['clicks'],
                    'impressions' => $r['impressions'],
                    'ctr'         => $r['ctr'],
                    'position'    => $r['position'],
                ])->all();
        });
    }

    public function byCountry(int $days = 28, int $limit = 15): array
    {
        return Cache::remember("gsc:country:{$days}:{$limit}", now()->addHours(6), function () use ($days, $limit) {
            [$start, $end] = $this->range($days);
            return collect($this->query($start, $end, ['country'], $limit))
                ->map(fn($r) => [
                    'country'     => strtoupper($r['keys'][0] ?? ''),
                    'clicks'      => $r['clicks'],
                    'impressions' => $r['impressions'],
                ])->all();
        });
    }
}
