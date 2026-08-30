<?php

namespace App\Support;

/**
 * Bounds for a user's personal rating and note, in one place — the validation
 * rules and the <x-rating-form> that produces the input both read them here, so
 * a form can't offer a value its own controller would then reject.
 */
class Rating
{
    /** Highest rating the form offers; the <select> is built from this. */
    public const MAX = 10;

    /** Matches the note textarea's maxlength. */
    public const MAX_NOTE_LENGTH = 2000;
}
