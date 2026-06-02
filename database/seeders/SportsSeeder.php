<?php

namespace Database\Seeders;

use App\Models\Sport;
use Illuminate\Database\Seeder;

class SportsSeeder extends Seeder
{
    public function run(): void
    {
        Sport::updateOrCreate(
            ['slug' => 'cricket'],
            [
                'name' => 'Cricket',
                'slug' => 'cricket',
                'color' => '#41EAD4',
                'api_sport_id' => 'cricket',
                'sort_order' => 1,
                'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M8 12h8M12 8v8"/></svg>',
                'scoring_format' => [
                    'fields' => ['runs', 'wickets', 'overs'],
                    'display' => '{runs}/{wickets} ({overs})',
                    'periods' => ['1st Innings', '2nd Innings'],
                ],
                'is_active' => true,
            ]
        );
    }
}
