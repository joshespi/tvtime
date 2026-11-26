<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserMovie;
use App\Models\UserShow;

/**
 * A user's own tracking rows (UserShow, UserMovie): only the owner may change
 * or remove them. Registered for both models in AppServiceProvider, since the
 * name doesn't match either for auto-discovery.
 */
class OwnedByUserPolicy
{
    public function update(User $user, UserShow|UserMovie $tracked): bool
    {
        return $user->id === $tracked->user_id;
    }

    public function delete(User $user, UserShow|UserMovie $tracked): bool
    {
        return $user->id === $tracked->user_id;
    }
}
