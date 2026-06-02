<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SportApiSyncLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'sync_type', 'sport_slug', 'status',
        'records_synced', 'api_calls_used',
        'error_message', 'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];
}
