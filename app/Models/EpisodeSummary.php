<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EpisodeSummary extends Model
{
    protected $fillable = [
        'source_title',
        'source_url',
        'published_at',
        'transcript',
        'summary',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];
}
