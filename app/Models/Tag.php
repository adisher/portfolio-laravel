<?php
namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use HasFactory, Sluggable;

    protected $fillable = ['name', 'slug', 'color'];

    public function sluggable(): array
    {
        return ['slug' => ['source' => 'name']];
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class);
    }

    public function blogPosts()
    {
        return $this->belongsToMany(BlogPost::class);
    }
}
