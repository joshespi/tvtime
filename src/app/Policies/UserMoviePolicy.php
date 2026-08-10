<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserMovie;

class UserMoviePolicy
{
    public function update(User $user, UserMovie $userMovie): bool
    {
        return $user->id === $userMovie->user_id;
    }

    public function delete(User $user, UserMovie $userMovie): bool
    {
        return $user->id === $userMovie->user_id;
    }
}
