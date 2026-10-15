<?php

namespace App\Models;

use Database\Factories\NotificationSentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationSent extends Model
{
    /** @use HasFactory<NotificationSentFactory> */
    use HasFactory;

    protected $table = 'notifications_sent';

    protected $fillable = ['user_id', 'subject_type', 'subject_id', 'channel', 'sent_at'];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }
}
