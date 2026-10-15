<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DismissedRecommendation extends Model
{
    protected $fillable = ['user_id', 'type', 'tmdb_id'];
}
