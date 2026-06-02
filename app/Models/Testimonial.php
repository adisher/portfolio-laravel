<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    use HasFactory;

    protected $fillable = [
        'type', 'client_name', 'client_position', 'client_company', 'client_role',
        'role_description', 'client_website', 'client_linkedin',
        'testimonial', 'client_image', 'rating',
        'is_featured', 'is_published', 'sort_order',
        'country_code', 'country_name', 'city', 'latitude', 'longitude',
    ];

    protected $casts = [
        'is_featured'  => 'boolean',
        'is_published' => 'boolean',
        'latitude'     => 'decimal:8',
        'longitude'    => 'decimal:8',
    ];

    /**
     * Get coordinates for globe display
     */
    public function getCoordinatesAttribute(): ?array
    {
        if ($this->latitude && $this->longitude) {
            return [
                'lat' => (float) $this->latitude,
                'lng' => (float) $this->longitude,
            ];
        }
        return null;
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }
}
