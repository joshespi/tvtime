<?php

namespace App\Services;

use RuntimeException;

/** TMDB has no show or movie for the requested id. */
class TitleNotFoundException extends RuntimeException {}
