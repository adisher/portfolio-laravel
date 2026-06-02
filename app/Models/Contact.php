<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'email', 'subject', 'message', 'status', 'read_at', 'admin_notes',
    ];

    protected $casts = ['read_at' => 'datetime'];

    public function scopeUnread($query)
    {
        return $query->where('status', 'unread');
    }

    public function markAsRead()
    {
        $this->update(['status' => 'read', 'read_at' => now()]);
    }
}
