<?php

namespace Database\Seeders;

use App\Models\Sport;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Database\Seeder;

class CricbuzzWorldCupSeeder extends Seeder
{
    public function run(): void
    {
        $sport = Sport::where('slug', 'cricket')->first();

        if (!$sport) {
            $this->command->error('Cricket sport not found. Run SportsSeeder first.');
            return;
        }

        $seriesId = config('sports.cricbuzz.series_id', '');

        // Create tournament
        Tournament::updateOrCreate(
            ['slug' => 'icc-t20-world-cup-2026'],
            [
                'name' => 'ICC T20 World Cup 2026',
                'short_name' => 'T20 WC 2026',
                'sport_id' => $sport->id,
                'api_tournament_id' => $seriesId ? "cricbuzz:{$seriesId}" : 'cricbuzz:t20wc2026',
                'season' => '2026',
                'is_active' => true,
                'start_date' => '2026-02-01',
                'end_date' => '2026-03-31',
            ]
        );

        // ICC T20 WC teams with country codes for flag URLs
        $teams = [
            ['name' => 'India', 'abbreviation' => 'IND', 'country_code' => 'in'],
            ['name' => 'Australia', 'abbreviation' => 'AUS', 'country_code' => 'au'],
            ['name' => 'England', 'abbreviation' => 'ENG', 'country_code' => 'gb'],
            ['name' => 'Pakistan', 'abbreviation' => 'PAK', 'country_code' => 'pk'],
            ['name' => 'South Africa', 'abbreviation' => 'SA', 'country_code' => 'za'],
            ['name' => 'New Zealand', 'abbreviation' => 'NZ', 'country_code' => 'nz'],
            ['name' => 'West Indies', 'abbreviation' => 'WI', 'country_code' => 'jm'],
            ['name' => 'Sri Lanka', 'abbreviation' => 'SL', 'country_code' => 'lk'],
            ['name' => 'Bangladesh', 'abbreviation' => 'BAN', 'country_code' => 'bd'],
            ['name' => 'Afghanistan', 'abbreviation' => 'AFG', 'country_code' => 'af'],
            ['name' => 'Canada', 'abbreviation' => 'CAN', 'country_code' => 'ca'],
            ['name' => 'Ireland', 'abbreviation' => 'IRE', 'country_code' => 'ie'],
            ['name' => 'Italy', 'abbreviation' => 'ITA', 'country_code' => 'it'],
            ['name' => 'Zimbabwe', 'abbreviation' => 'ZIM', 'country_code' => 'zw'],
            ['name' => 'Scotland', 'abbreviation' => 'SCO', 'country_code' => 'gb'],
            ['name' => 'Netherlands', 'abbreviation' => 'NED', 'country_code' => 'nl'],
            ['name' => 'Namibia', 'abbreviation' => 'NAM', 'country_code' => 'na'],
            ['name' => 'Nepal', 'abbreviation' => 'NEP', 'country_code' => 'np'],
            ['name' => 'Oman', 'abbreviation' => 'OMN', 'country_code' => 'om'],
            ['name' => 'Papua New Guinea', 'abbreviation' => 'PNG', 'country_code' => 'pg'],
            ['name' => 'Uganda', 'abbreviation' => 'UGA', 'country_code' => 'ug'],
            ['name' => 'United Arab Emirates', 'abbreviation' => 'UAE', 'country_code' => 'ae'],
            ['name' => 'United States', 'abbreviation' => 'USA', 'country_code' => 'us'],
        ];

        foreach ($teams as $teamData) {
            Team::updateOrCreate(
                [
                    'abbreviation' => $teamData['abbreviation'],
                    'sport_id' => $sport->id,
                ],
                [
                    'name' => $teamData['name'],
                    'short_name' => $teamData['name'],
                    'api_team_id' => 'cricbuzz:' . strtolower($teamData['abbreviation']),
                    'logo' => "https://flagcdn.com/w80/{$teamData['country_code']}.png",
                    'country' => $teamData['name'],
                    'country_code' => $teamData['country_code'],
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('Seeded T20 World Cup 2026 tournament + ' . count($teams) . ' teams.');
    }
}
