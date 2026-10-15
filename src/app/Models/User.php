<?php

namespace App\Models;

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

    /**
     * This user's tracking record for a show, or null if they don't track it —
     * the ownership check for routes keyed by a Show or Episode rather than a
     * UserShow. Callers pick the failure: reads 404 (don't confirm the id
     * exists), writes 403. `show` is set from the model passed in, since callers
     * label things by its title.
     */
    public function trackedShow(Show $show): ?UserShow
    {
        return $this->shows()->where('show_id', $show->id)->first()?->setRelation('show', $show);
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

    /** Recommendations this user has said "not interested" to. */
    public function dismissedRecommendations(): HasMany
    {
        return $this->hasMany(DismissedRecommendation::class);
    }
}
