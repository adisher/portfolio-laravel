<?php

namespace Tests\Feature;

use App\Models\FeatureFlag;
use Database\Seeders\FeatureFlagSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Re-running the seeder (e.g. after a deploy adds a new flag) must only add
 * missing flags and never overwrite what was set in Admin > Visibility.
 */
class FeatureFlagSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_reseeding_keeps_existing_flag_settings(): void
    {
        FeatureFlag::create(['key' => 'nav.sports', 'group' => 'nav', 'label' => 'Nav: Sports', 'is_enabled' => false]);
        FeatureFlag::create([
            'key' => 'banner.global', 'group' => 'banner', 'label' => 'Announcement Banner', 'is_enabled' => true,
            'metadata' => ['message' => 'Big news', 'link' => '/blog', 'link_text' => 'Read', 'color' => 'sunset'],
        ]);

        $this->seed(FeatureFlagSeeder::class);

        $this->assertFalse(FeatureFlag::where('key', 'nav.sports')->first()->is_enabled);

        $banner = FeatureFlag::where('key', 'banner.global')->first();
        $this->assertTrue($banner->is_enabled);
        $this->assertSame('Big news', $banner->metadata['message']);
    }

    public function test_missing_flags_are_created_with_their_defaults(): void
    {
        $this->seed(FeatureFlagSeeder::class);

        $this->assertTrue(FeatureFlag::where('key', 'page.cinema')->first()->is_enabled);
        $this->assertFalse(FeatureFlag::where('key', 'banner.global')->first()->is_enabled);
    }
}
