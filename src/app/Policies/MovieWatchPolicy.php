<?php

namespace App\Policies;

use App\Models\MovieWatch;
use App\Models\User;

class MovieWatchPolicy
{
    public function delete(User $user, MovieWatch $movieWatch): bool
    {
        return $user->id === $movieWatch->userMovie->user_id;
    }
}
