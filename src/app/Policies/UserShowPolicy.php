<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserShow;

class UserShowPolicy
{
    public function update(User $user, UserShow $userShow): bool
    {
        return $user->id === $userShow->user_id;
    }

    public function delete(User $user, UserShow $userShow): bool
    {
        return $user->id === $userShow->user_id;
    }
}
