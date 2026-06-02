<?php

namespace App\Services\Sports;

use App\Models\Sport;
use App\Models\SportApiSyncLog;
use App\Models\SportMatch;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CricbuzzApiService
{
    protected string $baseUrl;
    protected string $seriesId;
    protected int $timeout;

    private const TEAM_COUNTRY_CODES = [
        'IND' => 'in', 'AUS' => 'au', 'ENG' => 'gb', 'PAK' => 'pk',
        'SA' => 'za', 'NZ' => 'nz', 'WI' => 'jm', 'SL' => 'lk',
        'BAN' => 'bd', 'AFG' => 'af', 'IRE' => 'ie', 'ZIM' => 'zw',
        'SCO' => 'gb', 'NED' => 'nl', 'NAM' => 'na', 'NEP' => 'np',
        'OMN' => 'om', 'PNG' => 'pg', 'UGA' => 'ug', 'USA' => 'us',
        'CAN' => 'ca', 'ITA' => 'it', 'UAE' => 'ae', 'RSA' => 'za',
    ];

    public function __construct()
    {
        $this->baseUrl = rtrim(config('sports.cricbuzz.base_url', 'http://localhost:5000'), '/');
        $this->seriesId = config('sports.cricbuzz.series_id', '');
        $this->timeout = config('sports.cricbuzz.timeout', 15);
    }

    // =========================================================================
    // FETCH — call the Python Flask scraper
    // =========================================================================

    /**
     * Fetch all matches for the configured series from Flask.
     */
    public function fetchSeriesMatches(): ?array
    {
        if (empty($this->seriesId)) {
            Log::warning('Cricbuzz series ID not configured');
            return null;
        }

        $cacheKey = "cricbuzz:series:{$this->seriesId}";
        $ttl = config('sports.cache.series_matches_ttl', 1800);

        return Cache::remember($cacheKey, $ttl, function () {
            return $this->makeRequest("/series/{$this->seriesId}/matches");
        });
    }

    /**
     * Fetch detailed match data from Flask.
     */
    public function fetchMatchDetails(int $cricbuzzMatchId): ?array
    {
        $cacheKey = "cricbuzz:match:{$cricbuzzMatchId}";

        // Shorter TTL for live, longer for completed
        $match = SportMatch::where('api_match_id', "cricbuzz:{$cricbuzzMatchId}")->first();
        $ttl = ($match && $match->status === 'live')
            ? config('sports.cache.match_detail_ttl', 30)
            : config('sports.cache.completed_match_ttl', 3600);

        return Cache::remember($cacheKey, $ttl, function () use ($cricbuzzMatchId) {
            return $this->makeRequest("/match/{$cricbuzzMatchId}");
        });
    }

    // =========================================================================
    // LAZY SYNC — auto-fetch on page visit when data is stale
    // =========================================================================

    /**
     * Perform a sync if the last sync was older than the configured TTL.
     * Called from controllers as a fallback when the scheduler isn't running.
     */
    public function lazySyncIfStale(): void
    {
        if (!config('sports.lazy_sync.enabled', true)) {
            return;
        }

        $cacheKey = 'sports:last_lazy_sync';

        // Use shorter TTL (2 min) when live matches exist, otherwise 10 min
        $hasLive = SportMatch::where('status', 'live')->exists();
        $ttl = $hasLive ? 120 : config('sports.lazy_sync.ttl', 600);

        if (Cache::has($cacheKey)) {
            return;
        }

        Cache::put($cacheKey, now()->timestamp, $ttl);

        try {
            $this->syncSeriesMatches();

            // Also sync live match details for fresh batsmen/bowler data
            if ($hasLive) {
                $this->syncLiveScores();
            }

            Log::debug('Cricbuzz lazy sync completed');
        } catch (\Exception $e) {
            Log::warning('Cricbuzz lazy sync failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Clear cached data for fresh fetch.
     */
    public function clearCache(): void
    {
        Cache::forget("cricbuzz:series:{$this->seriesId}");
        Cache::forget('sports:last_lazy_sync');
        Cache::forget('sports:live_matches');

        // Clear individual match caches for live matches
        SportMatch::live()->pluck('api_match_id')->each(function ($apiId) {
            $id = str_replace('cricbuzz:', '', $apiId);
            Cache::forget("cricbuzz:match:{$id}");
        });
    }

    // =========================================================================
    // SYNC — persist Cricbuzz data to database
    // =========================================================================

    /**
     * Sync all matches from the series into the database.
     */
    public function syncSeriesMatches(): int
    {
        $synced = 0;

        try {
            // Bypass cache for sync
            Cache::forget("cricbuzz:series:{$this->seriesId}");
            $data = $this->fetchSeriesMatches();

            if (!$data || !isset($data['matches'])) {
                Log::warning('No matches data from Cricbuzz scraper');
                $this->logSync('matches', 'failed', 0, 'No data returned');
                return 0;
            }

            $sport = Sport::where('slug', 'cricket')->first();
            if (!$sport) {
                Log::error('Cricket sport not found in database. Run SportsSeeder first.');
                return 0;
            }

            $tournament = $this->getOrCreateTournament($sport);

            foreach ($data['matches'] as $matchData) {
                try {
                    $this->upsertMatch($matchData, $sport, $tournament);
                    $synced++;
                } catch (\Exception $e) {
                    Log::warning('Failed to upsert match', [
                        'match_id' => $matchData['id'] ?? 'unknown',
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $this->logSync('matches', 'success', $synced);
        } catch (\Exception $e) {
            Log::error('Cricbuzz series sync failed', ['error' => $e->getMessage()]);
            $this->logSync('matches', 'failed', $synced, $e->getMessage());
        }

        return $synced;
    }

    /**
     * Sync live match details (batsmen, bowler, recent balls).
     */
    public function syncLiveScores(): array
    {
        $updatedMatches = [];

        try {
            $liveMatches = SportMatch::live()
                ->whereNotNull('api_match_id')
                ->get();

            foreach ($liveMatches as $match) {
                $updated = $this->syncMatchDetails($match);
                if ($updated) {
                    $updatedMatches[] = $match->fresh(['sport', 'homeTeam', 'awayTeam', 'tournament']);
                }
            }

            if (count($updatedMatches) > 0) {
                Cache::forget('sports:live_matches');
            }

            $this->logSync('live_scores', 'success', count($updatedMatches));
        } catch (\Exception $e) {
            Log::error('Live scores sync failed', ['error' => $e->getMessage()]);
            $this->logSync('live_scores', 'failed', 0, $e->getMessage());
        }

        return $updatedMatches;
    }

    /**
     * Sync details for a specific match from the Flask API.
     */
    public function syncMatchDetails(SportMatch $match): bool
    {
        if (!$match->api_match_id) {
            return false;
        }

        $cricbuzzId = (int) str_replace('cricbuzz:', '', $match->api_match_id);

        try {
            // Bypass cache for live detail
            if ($match->status === 'live') {
                Cache::forget("cricbuzz:match:{$cricbuzzId}");
            }

            $data = $this->fetchMatchDetails($cricbuzzId);
            if (!$data || isset($data['error'])) {
                return false;
            }

            $previousStatus = $match->status;
            $newStatus = $this->mapState($data['match_state'] ?? 'upcoming');

            // Build metadata from live/completed detail
            $metadata = $match->metadata ?? [];

            if ($newStatus === 'live') {
                $metadata['match_status'] = $data['match_status'] ?? null;
                $metadata['batsmen'] = $data['batsmen'] ?? [];
                $metadata['bowler'] = $data['bowler'] ?? [];
                $metadata['recent_balls'] = $data['recent_balls'] ?? [];
                $metadata['latest_ball'] = $data['latest_ball'] ?? null;
                $metadata['team_scores'] = $data['team_scores'] ?? [];

                // Parse team scores into home/away (only overwrite non-null values)
                $scores = $this->parseTeamScoresFromDetail($data, $match);
                if ($scores) {
                    if ($scores['home'] !== null) $match->home_score = $scores['home'];
                    if ($scores['away'] !== null) $match->away_score = $scores['away'];
                }

                $match->result_summary = $data['match_status'] ?? $match->result_summary;
            } elseif ($newStatus === 'completed') {
                $metadata['result'] = $data['result'] ?? null;
                $metadata['player_of_match'] = $data['player_of_match'] ?? [];
                $metadata['team_scores'] = $data['team_scores'] ?? [];

                // Parse final scores (only overwrite non-null values)
                $scores = $this->parseTeamScoresFromDetail($data, $match);
                if ($scores) {
                    if ($scores['home'] !== null) $match->home_score = $scores['home'];
                    if ($scores['away'] !== null) $match->away_score = $scores['away'];
                }

                $match->result_summary = $data['result'] ?? $match->result_summary;
            } elseif ($newStatus === 'scheduled') {
                $metadata['start_info'] = $data['start_info'] ?? [];
            }

            // Track status transitions
            if ($previousStatus !== 'live' && $newStatus === 'live' && !$match->started_at) {
                $match->started_at = now();
            }
            if ($previousStatus !== 'completed' && $newStatus === 'completed' && !$match->ended_at) {
                $match->ended_at = now();
            }

            $match->status = $newStatus;
            $match->metadata = $metadata;
            $match->save();

            return true;
        } catch (\Exception $e) {
            Log::error('Match detail sync failed', [
                'match_id' => $match->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    // =========================================================================
    // UPSERT & MATCHING
    // =========================================================================

    /**
     * Upsert a match from series listing data.
     */
    protected function upsertMatch(array $matchData, Sport $sport, Tournament $tournament): SportMatch
    {
        $cricbuzzId = $matchData['id'] ?? null;
        $apiMatchId = "cricbuzz:{$cricbuzzId}";

        // Find teams by abbreviation
        $team1Abbr = $matchData['teams']['team1']['short'] ?? '';
        $team2Abbr = $matchData['teams']['team2']['short'] ?? '';
        $team1Full = $matchData['teams']['team1']['full'] ?? $team1Abbr;
        $team2Full = $matchData['teams']['team2']['full'] ?? $team2Abbr;

        $homeTeam = $this->findOrCreateTeam($team1Abbr, $team1Full, $sport);
        $awayTeam = $this->findOrCreateTeam($team2Abbr, $team2Full, $sport);

        $state = $matchData['state'] ?? 'upcoming';
        $status = $this->mapState($state);

        // Parse scores from series listing (format: "IND 180/5 (20.0)")
        $homeScore = isset($matchData['scores']['team1'])
            ? $this->parseCricbuzzScore($matchData['scores']['team1'])
            : null;
        $awayScore = isset($matchData['scores']['team2'])
            ? $this->parseCricbuzzScore($matchData['scores']['team2'])
            : null;

        // Parse date — use start_date (Unix timestamp ms) or fallback to date string
        $scheduledAt = $this->parseMatchDate($matchData['date'] ?? '', $matchData['start_date'] ?? '');

        // Venue — Flask returns an object {ground, city}, flatten to string
        $venue = $matchData['venue'] ?? null;
        if (is_array($venue)) {
            $venue = trim(($venue['ground'] ?? '') . (!empty($venue['city']) ? ', ' . $venue['city'] : ''));
        }

        $title = ($homeTeam?->name ?? $team1Full) . ' vs ' . ($awayTeam?->name ?? $team2Full);

        $match = SportMatch::updateOrCreate(
            ['api_match_id' => $apiMatchId],
            [
                'title' => $title,
                'slug' => Str::slug($title . '-' . ($scheduledAt ? $scheduledAt->format('Y-m-d') : now()->format('Y-m-d'))),
                'sport_id' => $sport->id,
                'tournament_id' => $tournament->id,
                'home_team_id' => $homeTeam?->id,
                'away_team_id' => $awayTeam?->id,
                'status' => $status,
                'match_type' => $matchData['match_desc'] ?? null,
                'venue' => $venue,
                'scheduled_at' => $scheduledAt,
                'home_score' => $homeScore,
                'away_score' => $awayScore,
                'result_summary' => in_array($status, ['completed', 'live', 'abandoned']) ? ($matchData['status'] ?? null) : null,
                'meta_description' => Str::limit("Live scores for {$title} - T20 World Cup 2026", 160),
            ]
        );

        return $match;
    }

    /**
     * Find or create a team by abbreviation.
     */
    protected function findOrCreateTeam(string $abbreviation, string $fullName, Sport $sport): ?Team
    {
        if (empty($abbreviation) && empty($fullName)) {
            return null;
        }

        $abbr = strtoupper(trim($abbreviation));

        // Try finding by abbreviation first
        if ($abbr) {
            $team = Team::where('abbreviation', $abbr)
                ->where('sport_id', $sport->id)
                ->first();

            if ($team) {
                return $team;
            }
        }

        // Try finding by name
        if ($fullName) {
            $team = Team::where('name', $fullName)
                ->where('sport_id', $sport->id)
                ->first();

            if ($team) {
                return $team;
            }
        }

        // Create new team with flag if country code is known
        $countryCode = self::TEAM_COUNTRY_CODES[$abbr] ?? null;

        return Team::create([
            'name' => $fullName ?: $abbr,
            'short_name' => $fullName ?: $abbr,
            'abbreviation' => $abbr ?: strtoupper(substr($fullName, 0, 3)),
            'sport_id' => $sport->id,
            'api_team_id' => 'cricbuzz:' . Str::slug($abbr ?: $fullName),
            'country_code' => $countryCode,
            'logo' => $countryCode ? "https://flagcdn.com/w80/{$countryCode}.png" : null,
            'is_active' => true,
        ]);
    }

    /**
     * Get or create the World Cup tournament.
     */
    protected function getOrCreateTournament(Sport $sport): Tournament
    {
        $apiTournamentId = "cricbuzz:{$this->seriesId}";

        // Try finding by api_tournament_id first, then by slug (seeder may have used a different api ID)
        $tournament = Tournament::where('api_tournament_id', $apiTournamentId)->first()
            ?? Tournament::where('slug', 'icc-t20-world-cup-2026')->first();

        if ($tournament) {
            // Ensure the api_tournament_id is up to date
            if ($tournament->api_tournament_id !== $apiTournamentId) {
                $tournament->update(['api_tournament_id' => $apiTournamentId]);
            }
            return $tournament;
        }

        return Tournament::create([
            'api_tournament_id' => $apiTournamentId,
            'name' => 'ICC T20 World Cup 2026',
            'short_name' => 'T20 WC 2026',
            'slug' => 'icc-t20-world-cup-2026',
            'sport_id' => $sport->id,
            'season' => '2026',
            'is_active' => true,
            'start_date' => '2026-02-01',
            'end_date' => '2026-03-31',
        ]);
    }

    // =========================================================================
    // SCORE PARSING
    // =========================================================================

    /**
     * Parse a Cricbuzz score string into structured data.
     * Formats: "IND 180/5 (20.0)", "180/5 (20.0)", "180/5", "180 (all out)"
     */
    public function parseCricbuzzScore(string $scoreStr): ?array
    {
        $scoreStr = trim($scoreStr);
        if (empty($scoreStr) || $scoreStr === '-') {
            return null;
        }

        // Remove team abbreviation prefix (e.g., "IND 180/5 (20.0)" → "180/5 (20.0)")
        $scoreStr = preg_replace('/^[A-Z]{2,4}\s+/', '', $scoreStr);

        $runs = 0;
        $wickets = 0;
        $overs = '0';

        // Extract overs from parentheses: "(20.0)" or "(19.4 Ov)"
        if (preg_match('/\(([0-9.]+)/', $scoreStr, $oversMatch)) {
            $overs = $oversMatch[1];
        }

        // Remove the overs part for runs/wickets parsing
        $scoreStr = trim(preg_replace('/\s*\(.*\)/', '', $scoreStr));

        // Parse "180/5" or "180"
        if (str_contains($scoreStr, '/')) {
            $parts = explode('/', $scoreStr);
            $runs = (int) trim($parts[0]);
            $wickets = (int) trim($parts[1] ?? '0');
        } else {
            $runs = (int) $scoreStr;
        }

        return [
            'runs' => $runs,
            'wickets' => $wickets,
            'overs' => $overs,
        ];
    }

    /**
     * Parse team scores from match detail response.
     * Detail endpoint returns team_scores with 'batting'/'bowling' keys like "ZIM 62/0 (6.6) CRR: 8.86 REQ: 9"
     * Series endpoint returns team_scores with 'team1'/'team2' keys like "147/10 (19.5)"
     */
    protected function parseTeamScoresFromDetail(array $data, SportMatch $match): ?array
    {
        $teamScores = $data['team_scores'] ?? [];
        if (empty($teamScores)) {
            return null;
        }

        // Collect all score strings — detail uses 'batting'/'bowling', series uses 'team1'/'team2'
        $scoreStrings = [];
        foreach (['batting', 'bowling', 'team1', 'team2'] as $key) {
            if (isset($teamScores[$key]) && is_string($teamScores[$key]) && trim($teamScores[$key]) !== '') {
                $scoreStrings[] = $teamScores[$key];
            }
        }

        if (empty($scoreStrings)) {
            return null;
        }

        // Match each score string to home/away by checking team abbreviation at start
        $homeAbbr = strtoupper($match->homeTeam?->abbreviation ?? '');
        $awayAbbr = strtoupper($match->awayTeam?->abbreviation ?? '');

        $homeScore = null;
        $awayScore = null;

        foreach ($scoreStrings as $str) {
            $upper = strtoupper($str);
            $parsed = $this->parseCricbuzzScore($str);

            if ($homeAbbr && str_starts_with($upper, $homeAbbr . ' ')) {
                $homeScore = $parsed;
            } elseif ($awayAbbr && str_starts_with($upper, $awayAbbr . ' ')) {
                $awayScore = $parsed;
            }
        }

        // Only return if we identified at least one score (prevents overwriting good data)
        if ($homeScore === null && $awayScore === null) {
            return null;
        }

        return ['home' => $homeScore, 'away' => $awayScore];
    }

    // =========================================================================
    // DATE PARSING
    // =========================================================================

    /**
     * Parse Cricbuzz date + start_date into a Carbon datetime.
     * start_date may be a Unix timestamp (milliseconds) like "1740000000000"
     * or a human-readable string. Falls back to dateStr ("Feb 17 - Tuesday").
     */
    protected function parseMatchDate(string $dateStr, string $startDate): ?Carbon
    {
        // Prefer start_date if it looks like a Unix timestamp (ms or s)
        if (!empty($startDate) && is_numeric($startDate)) {
            try {
                $ts = (int) $startDate;
                if ($ts > 1e12) {
                    $ts = intdiv($ts, 1000); // milliseconds → seconds
                }
                return Carbon::createFromTimestamp($ts);
            } catch (\Exception $e) {
                // Fall through to string parsing
            }
        }

        // Fallback: parse human-readable date string like "Feb 17 - Tuesday"
        if (empty($dateStr)) {
            return null;
        }

        try {
            // Clean date string: "Feb 17 - Tuesday" → "Feb 17"
            $cleaned = preg_replace('/\s*-\s*\w+day$/i', '', trim($dateStr));

            // Add current year
            $year = now()->year;
            $cleaned = trim($cleaned) . " {$year}";

            return Carbon::parse($cleaned);
        } catch (\Exception $e) {
            Log::debug('Failed to parse match date', ['date' => $dateStr, 'start_date' => $startDate, 'error' => $e->getMessage()]);
            return null;
        }
    }

    // =========================================================================
    // STATUS MAPPING
    // =========================================================================

    /**
     * Map Cricbuzz state to our status enum.
     */
    protected function mapState(string $state): string
    {
        return match (strtolower(trim($state))) {
            'upcoming', 'preview', 'pre', 'scheduled' => 'scheduled',
            'live', 'in_progress', 'in progress', 'innings break' => 'live',
            'completed', 'complete', 'post', 'result' => 'completed',
            'abandoned', 'abandon' => 'abandoned',
            'cancelled', 'canceled' => 'cancelled',
            'postponed' => 'postponed',
            default => 'scheduled',
        };
    }

    // =========================================================================
    // HTTP & LOGGING
    // =========================================================================

    /**
     * Make an HTTP request to the Python Flask API.
     */
    protected function makeRequest(string $endpoint): ?array
    {
        try {
            $url = $this->baseUrl . $endpoint;

            $response = Http::timeout($this->timeout)
                ->withHeaders(['Accept' => 'application/json'])
                ->get($url);

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning('Cricbuzz API request failed', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Cricbuzz API request exception', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Log a sync operation.
     */
    protected function logSync(string $type, string $status, int $records, ?string $error = null): void
    {
        SportApiSyncLog::create([
            'sync_type' => $type,
            'sport_slug' => 'cricket',
            'status' => $status,
            'records_synced' => $records,
            'api_calls_used' => 1,
            'error_message' => $error,
        ]);
    }
}
