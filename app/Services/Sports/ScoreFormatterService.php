<?php

namespace App\Services\Sports;

use App\Models\Sport;
use App\Models\SportMatch;

class ScoreFormatterService
{
    /**
     * Format a score array for display based on sport type.
     */
    public function format(?array $scoreData, Sport $sport): string
    {
        if (!$scoreData) {
            return '-';
        }

        $format = $sport->scoring_format['display'] ?? '{score}';

        $result = $format;
        foreach ($scoreData as $key => $value) {
            $result = str_replace('{' . $key . '}', (string) $value, $result);
        }

        return $result;
    }

    /**
     * Format a complete match summary string.
     */
    public function formatMatchSummary(SportMatch $match): string
    {
        if ($match->result_summary) {
            return $match->result_summary;
        }

        $home = $match->homeTeam->short_name ?? $match->homeTeam->name;
        $away = $match->awayTeam->short_name ?? $match->awayTeam->name;

        $homeScore = $match->formatted_home_score;
        $awayScore = $match->formatted_away_score;

        return "{$home} {$homeScore} vs {$away} {$awayScore}";
    }

    /**
     * Get structured score parts for template rendering.
     */
    public function getScoreDisplayParts(SportMatch $match): array
    {
        return [
            'home_team' => $match->homeTeam->short_name ?? $match->homeTeam->name,
            'away_team' => $match->awayTeam->short_name ?? $match->awayTeam->name,
            'home_score' => $match->formatted_home_score,
            'away_score' => $match->formatted_away_score,
            'status' => $match->status,
            'period' => $match->current_period,
            'match_time' => $match->match_time,
            'sport' => $match->sport->name,
            'sport_slug' => $match->sport->slug,
            'sport_color' => $match->sport->color,
        ];
    }
}
