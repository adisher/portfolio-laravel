<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A record of one Find Voices run (Brave or Claude), for transparency: what was
 * searched, what came back, cost, and why it was empty.
 */
class VoiceSearchRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_item_id', 'engine', 'queries', 'candidates_found', 'status', 'cost_usd', 'raw', 'note',
    ];

    protected $casts = [
        'queries'  => 'array',
        'cost_usd' => 'decimal:6',
    ];

    public function workItem(): BelongsTo
    {
        return $this->belongsTo(WorkItem::class);
    }
}
