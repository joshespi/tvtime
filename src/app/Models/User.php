<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use NotificationChannels\WebPush\HasPushSubscriptions;

#[Fillable(['name', 'email', 'password', 'recommendations_enabled'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasPushSubscriptions, Notifiable;

    /** Mirrors the column default so a freshly built User reads the same as a reloaded one. */
    protected $attributes = [
        'recommendations_enabled' => true,
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'recommendations_enabled' => 'boolean',
        ];
    }

    public function shows(): HasMany
    {
        return $this->hasMany(UserShow::class);
    }

    public function movies(): HasMany
    {
        return $this->hasMany(UserMovie::class);
    }

    /** Every completed movie viewing, rewatches included. */
    public function movieWatches(): HasManyThrough
    {
        return $this->hasManyThrough(MovieWatch::class, UserMovie::class);
    }

    public function watchStatuses(): HasMany
    {
        return $this->hasMany(WatchStatus::class);
    }
}
