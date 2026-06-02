<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AutoPublishSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'enabled',
        'max_posts_per_day',
        'min_score_for_auto_publish',
        'require_review_below_score',
        'publish_times',
        'category_weights',
        'ai_enhancement_enabled',
        'include_faq_section',
        'include_key_insights',
        'include_tldr',
        'default_author_name',
        'posts_published_today',
        'last_publish_date',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'publish_times' => 'array',
        'category_weights' => 'array',
        'ai_enhancement_enabled' => 'boolean',
        'include_faq_section' => 'boolean',
        'include_key_insights' => 'boolean',
        'include_tldr' => 'boolean',
        'last_publish_date' => 'date',
    ];

    /**
     * Get the singleton instance of auto-publish settings.
     */
    public static function getInstance(): self
    {
        return static::firstOrCreate([], [
            'enabled' => true,
            'max_posts_per_day' => 3,
            'min_score_for_auto_publish' => 85,
            'require_review_below_score' => 75,
            'publish_times' => ['09:00', '13:00', '17:00'],
            'category_weights' => [
                'ai-machine-learning' => 30,
                'web-development' => 25,
                'tech-news' => 20,
                'programming' => 15,
                'design-ux' => 5,
                'devops-cloud' => 3,
                'career-growth' => 2,
            ],
            'ai_enhancement_enabled' => true,
            'include_faq_section' => true,
            'include_key_insights' => true,
            'include_tldr' => true,
        ]);
    }

    /**
     * Check if more posts can be published today.
     */
    public function canPublishMore(): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $this->resetDailyCountIfNeeded();

        return $this->posts_published_today < $this->max_posts_per_day;
    }

    /**
     * Get remaining posts for today.
     */
    public function getRemainingPostsAttribute(): int
    {
        $this->resetDailyCountIfNeeded();

        return max(0, $this->max_posts_per_day - $this->posts_published_today);
    }

    /**
     * Increment the daily post count.
     */
    public function incrementPostCount(): void
    {
        $this->resetDailyCountIfNeeded();
        $this->increment('posts_published_today');
        $this->update(['last_publish_date' => now()->toDateString()]);
    }

    /**
     * Reset daily count if it's a new day.
     */
    protected function resetDailyCountIfNeeded(): void
    {
        if ($this->last_publish_date === null || !$this->last_publish_date->isToday()) {
            $this->update([
                'posts_published_today' => 0,
                'last_publish_date' => now()->toDateString(),
            ]);
            $this->refresh();
        }
    }

    /**
     * Check if an article should be auto-approved based on score.
     */
    public function shouldAutoApprove(float $score): bool
    {
        return $score >= $this->min_score_for_auto_publish;
    }

    /**
     * Check if an article needs manual review.
     */
    public function needsManualReview(float $score): bool
    {
        return $score < $this->min_score_for_auto_publish && $score >= $this->require_review_below_score;
    }

    /**
     * Check if an article should be rejected.
     */
    public function shouldReject(float $score): bool
    {
        return $score < $this->require_review_below_score;
    }

    /**
     * Get the weight for a category.
     */
    public function getCategoryWeight(string $categorySlug): int
    {
        return $this->category_weights[$categorySlug] ?? 0;
    }

    /**
     * Get next publish time.
     */
    public function getNextPublishTime(): ?string
    {
        $now = now()->format('H:i');

        foreach ($this->publish_times as $time) {
            if ($time > $now) {
                return $time;
            }
        }

        return $this->publish_times[0] ?? null;
    }
}
