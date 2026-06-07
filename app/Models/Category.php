<?php
namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory, Sluggable;

    protected $fillable = ['name', 'slug', 'description', 'color', 'is_active', 'keywords', 'for_blog', 'for_projects'];

    protected $casts = [
        'is_active' => 'boolean',
        'for_blog' => 'boolean',
        'for_projects' => 'boolean',
        'keywords' => 'array',
    ];

    public function sluggable(): array
    {
        return ['slug' => ['source' => 'name']];
    }

    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    public function blogPosts()
    {
        return $this->hasMany(BlogPost::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForBlog($query)
    {
        return $query->where('for_blog', true);
    }

    public function scopeForProjects($query)
    {
        return $query->where('for_projects', true);
    }
}
