<?php

use App\Models\UserShow;
use App\Support\TrackingStatus;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Fills the new Finished shelf on deploy. Without this, a show that's
     * already caught up only moves there the next time an episode is toggled
     * or the daily episode check runs — so the feature would look broken for
     * a day. Uses the model deliberately: "finished" means the same thing here
     * as it does at runtime, and there are only a handful of rows per user.
     */
    public function up(): void
    {
        UserShow::query()
            ->where('status', TrackingStatus::Watching)
            ->each(fn (UserShow $userShow) => $userShow->refreshFinishedStatus());
    }

    public function down(): void
    {
        UserShow::query()
            ->where('status', TrackingStatus::Finished)
            ->update(['status' => TrackingStatus::Watching]);
    }
};
