<?php
namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use League\CommonMark\CommonMarkConverter;

class Project extends Model
{
    use HasFactory, Sluggable;

    protected $fillable = [
        'title', 'slug', 'short_description', 'description', 'featured_image',
        'technologies', 'project_url', 'github_url', 'client_name', 'project_date',
        'status', 'is_featured', 'is_published', 'is_own_product', 'product_data', 'sort_order', 'category_id',
        'metrics', 'primary_metric_value', 'primary_metric_label',
        'challenge', 'solution', 'results', 'role', 'duration',
        'color_primary', 'color_secondary',
    ];

    protected $casts = [
        'technologies' => 'array',
        'metrics'      => 'array',
        'project_date' => 'date',
        'is_featured'     => 'boolean',
        'is_published'    => 'boolean',
        'is_own_product'  => 'boolean',
        'product_data'    => 'array',
    ];

    /**
     * Get the primary metric for display
     */
    public function getPrimaryMetricAttribute(): ?array
    {
        if ($this->primary_metric_value && $this->primary_metric_label) {
            return [
                'value' => $this->primary_metric_value,
                'label' => $this->primary_metric_label,
            ];
        }
        return null;
    }

    public function sluggable(): array
    {
        return ['slug' => ['source' => 'title']];
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    public function images()
    {
        return $this->hasMany(ProjectImage::class)->orderBy('sort_order');
    }

    public function productPages()
    {
        return $this->hasMany(ProductPage::class)->orderBy('sort_order');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeOwnProducts($query)
    {
        return $query->where('is_own_product', true);
    }

    public function scopeNotOwnProducts($query)
    {
        return $query->where('is_own_product', false);
    }

    public function getProductFeaturesAttribute(): ?array
    {
        return $this->product_data['features'] ?? null;
    }

    public function getProductHowItWorksAttribute(): ?array
    {
        return $this->product_data['how_it_works'] ?? null;
    }

    public function getProductPricingAttribute(): ?array
    {
        return $this->product_data['pricing'] ?? null;
    }

    public function getProductFaqAttribute(): ?array
    {
        return $this->product_data['faq'] ?? null;
    }

    public function getProductCtaUrlAttribute(): ?string
    {
        return $this->product_data['cta_url'] ?? null;
    }

    public function getProductCtaLabelAttribute(): ?string
    {
        return $this->product_data['cta_label'] ?? null;
    }

    /**
     * Render the description field as HTML from Markdown (used for product pages).
     */
    public function getRenderedDescriptionAttribute()
    {
        $converter = new CommonMarkConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);

        return $converter->convert($this->description)->getContent();
    }
}
