<?php

namespace App\Services\Shows;

use RuntimeException;

/** TMDB could not be reached (or errored), as opposed to answering "no such title". */
class TmdbUnavailableException extends RuntimeException {}
