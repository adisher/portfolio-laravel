<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkItemVoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_item_id', 'quote', 'attribution', 'source_url', 'media_id', 'status', 'sort_order', 'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function workItem(): BelongsTo
    {
        return $this->belongsTo(WorkItem::class);
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeCandidates($query)
    {
        return $query->where('status', 'candidate');
    }

    /**
     * One-line form the article generator reads. Includes the source and whether
     * a screenshot is attached, so the prompt can embed the image or a marker.
     */
    public function forPrompt(): string
    {
        $line = '"' . trim($this->quote) . '"';
        if ($this->attribution) {
            $line .= ' — ' . $this->attribution;
        }
        if ($this->source_url) {
            $line .= ' (source: ' . $this->source_url . ')';
        }
        $line .= $this->media_id
            ? ' [screenshot attached: embed it]'
            : ' [no screenshot: use a [[social:]] marker]';

        return $line;
    }
}
