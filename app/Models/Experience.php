<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Experience extends Model
{
    use HasFactory;

    protected $fillable = [
        'company', 'position', 'description', 'start_date', 'end_date',
        'is_current', 'location', 'company_url', 'company_logo', 'type', 'sort_order',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'is_current' => 'boolean',
    ];

    public function scopeWork($query)
    {
        return $query->where('type', 'work');
    }

    public function scopeEducation($query)
    {
        return $query->where('type', 'education');
    }

    public function getDurationAttribute()
    {
        $start = $this->start_date;
        $end   = $this->is_current ? now() : $this->end_date;

        return $start->diffInMonths($end);
    }
}
