<?php

namespace App\Models;

use App\Support\TrackingStatus;
use Database\Factories\UserShowFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserShow extends Model
{
    /** @use HasFactory<UserShowFactory> */
    use HasFactory;

    protected $fillable = ['user_id', 'show_id', 'status', 'rating', 'note'];

    protected function casts(): array
    {
        return [
            'status' => TrackingStatus::class,
        ];
    }

    /**
     * Only shows still being followed — watching or caught up. Everything that
     * pushes a show at the user — the dashboard, the calendar, notifications,
     * recommendation seeds — goes through this, so "stop watching" (paused or
     * dropped) silences all of them in one place. Finished shows deliberately
     * stay in: a caught-up show is exactly the one whose next season the user
     * still wants to hear about.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', TrackingStatus::activeValues());
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function show(): BelongsTo
    {
        return $this->belongsTo(Show::class);
    }

    /**
     * Moves the show between Watching and Finished to match whether anything
     * aired is still unwatched — so the Finished shelf fills itself when the
     * last episode is marked watched, and empties again when a new one airs.
     *
     * Only those two statuses are touched: Paused and Dropped are deliberate
     * choices the user made, and silently overriding them would be surprising.
     */
    public function refreshFinishedStatus(): void
    {
        if (! $this->status->isActive()) {
            return;
        }

        $aired = Episode::query()->where('show_id', $this->show_id)->aired();

        // The unwatched probe settles the common case on its own, so it goes
        // first and the second query only runs for a show that looks caught up.
        if ($aired->clone()->unwatchedBy($this->user_id)->exists()) {
            $status = TrackingStatus::Watching;
        } elseif ($aired->clone()->doesntExist()) {
            // A show whose episodes haven't synced yet has nothing aired, and
            // would otherwise read as "finished" the moment it was tracked.
            return;
        } else {
            $status = TrackingStatus::Finished;
        }

        if ($status !== $this->status) {
            $this->update(['status' => $status]);
        }
    }
}
