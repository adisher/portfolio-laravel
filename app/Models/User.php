<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'profile_picture',
        'bio',
        'job_title',
        'location',
        'website',
        'phone',
        'notification_preferences',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'notification_preferences' => 'array',
        'last_login_at' => 'datetime',
    ];

    protected $appends = ['profile_picture_url'];

    // Relationships
    public function blogPosts()
    {
        return $this->hasMany(BlogPost::class);
    }

    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    public function media()
    {
        return $this->hasMany(Media::class);
    }

    // Accessors
    public function getProfilePictureUrlAttribute()
    {
        if ($this->profile_picture) {
            return Storage::url($this->profile_picture);
        }
        
        // Return default avatar
        return $this->getGravatarUrl();
    }

    public function getGravatarUrl($size = 200)
    {
        $hash = md5(strtolower(trim($this->email)));
        return "https://www.gravatar.com/avatar/{$hash}?s={$size}&d=mp&r=g";
    }

    public function getInitialsAttribute()
    {
        $words = explode(' ', $this->name);
        $initials = '';
        foreach ($words as $word) {
            $initials .= strtoupper(substr($word, 0, 1));
        }
        return substr($initials, 0, 2);
    }

    // Get notification preference
    public function getNotificationPreference($key, $default = true)
    {
        $preferences = $this->notification_preferences ?? [];
        return $preferences[$key] ?? $default;
    }

    // Set notification preference
    public function setNotificationPreference($key, $value)
    {
        $preferences = $this->notification_preferences ?? [];
        $preferences[$key] = $value;
        $this->update(['notification_preferences' => $preferences]);
    }

    // Update last login
    public function updateLastLogin()
    {
        $this->update(['last_login_at' => now()]);
    }
}