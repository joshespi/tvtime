<?php

namespace App\Models;

use Database\Factories\NotificationSentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class NotificationSent extends Model
{
    /** @use HasFactory<NotificationSentFactory> */
    use HasFactory;

    protected $table = 'notifications_sent';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    /** The episode or movie this notification was about. */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
