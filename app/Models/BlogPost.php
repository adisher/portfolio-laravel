<?php
namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Feed\Feedable;
use Spatie\Feed\FeedItem;
use League\CommonMark\CommonMarkConverter;


class BlogPost extends Model implements Feedable
{
    use HasFactory, Sluggable;

    protected $fillable = [
        'title', 'slug', 'excerpt', 'content', 'featured_image', 'meta_title',
        'meta_description', 'meta_keywords', 'status', 'published_at', 'views',
        'reading_time', 'category_id', 'user_id', 'source_type', 'original_url', 'original_author', 
    'original_publication', 'original_published_at', 'curator_notes'
    ];

    protected $casts = [
        'meta_keywords' => 'array',
        'published_at'  => 'datetime',
        'original_published_at' => 'date',
    ];

    public function sluggable(): array
    {
        return ['slug' => ['source' => 'title']];
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published')->where('published_at', '<=', now());
    }

    public function getReadingTimeAttribute()
    {
        $wordCount = str_word_count(strip_tags($this->content));
        return ceil($wordCount / 200); // Average reading speed
    }

    public static function getFeedItems()
    {
        return static::published()
            ->with(['category', 'user', 'tags'])
            ->latest('published_at')
            ->take(20)
            ->get();
    }

    public static function getFeedItemsByCategory($category)
    {
        $categoryModel = \App\Models\Category::where('slug', $category)->first();
        
        if (!$categoryModel) {
            return collect();
        }

        return static::published()
            ->where('category_id', $categoryModel->id)
            ->with(['category', 'user', 'tags'])
            ->latest('published_at')
            ->take(20)
            ->get();
    }

    public function toFeedItem(): FeedItem
    {
        $summary = $this->excerpt;
        
        return FeedItem::create([
            'id' => $this->slug,
            'title' => $this->title,
            'summary' => $summary,
            'updated' => $this->updated_at,
            'link' => route('blog.show', $this->slug),
            'authorName' => $this->user->name,
            'authorEmail' => config('mail.from.address', 'noreply@portfolio.com'),
        ]);
    }

    public function getRenderedContentAttribute()
    {
        // External links (our citations) get a 'ref-link' class so they can be
        // styled as small, unobtrusive references. Internal links are untouched.
        $host = parse_url(config('app.url'), PHP_URL_HOST) ?: 'localhost';

        $environment = new \League\CommonMark\Environment\Environment([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'external_link' => [
                'internal_hosts' => $host,
                'open_in_new_window' => true,
                'html_class' => 'ref-link',
                'noopener' => 'external',
                'noreferrer' => 'external',
            ],
        ]);
        $environment->addExtension(new \League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension());
        $environment->addExtension(new \League\CommonMark\Extension\ExternalLink\ExternalLinkExtension());

        return (new \League\CommonMark\MarkdownConverter($environment))
            ->convert($this->content)
            ->getContent();
    }
}
