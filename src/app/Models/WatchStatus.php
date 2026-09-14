<?php

namespace App\Models;

use Database\Factories\WatchStatusFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WatchStatus extends Model
{
    /** @use HasFactory<WatchStatusFactory> */
    use HasFactory;

    protected $table = 'watch_status';

    protected $fillable = ['user_id', 'episode_id', 'watched_at'];

    protected function casts(): array
    {
        return [
            'watched_at' => 'datetime',
        ];
    }

    public function episode(): BelongsTo
    {
        return $this->belongsTo(Episode::class);
    }
}
