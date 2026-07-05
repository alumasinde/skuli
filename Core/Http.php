<?php
declare(strict_types=1);

namespace Core;

/**
 * Http — named HTTP status codes, the direct PHP equivalent of Go's
 * http.StatusOK / http.StatusBadRequest constants.
 *
 * A backed enum (PHP 8.1+) rather than a class of int constants because it
 * gives you an actual closed, typed set — Http::OK is a real value with a
 * type (Http), not just an int that happens to equal 200. IDEs autocomplete
 * the full list, and a function typed to accept `Http` can't accidentally
 * be passed a random integer.
 *
 * Only the codes this app actually uses are included — add more as needed,
 * this isn't meant to be an exhaustive RFC list.
 */
enum Http: int
{
    case OK                  = 200;
    case CREATED              = 201;
    case NO_CONTENT           = 204;
    case BAD_REQUEST          = 400;
    case UNAUTHORIZED         = 401;
    case FORBIDDEN            = 403;
    case NOT_FOUND            = 404;
    case CONFLICT             = 409;
    case UNPROCESSABLE_ENTITY = 422;
    case TOO_MANY_REQUESTS    = 429;
    case SERVER_ERROR         = 500;

    /** Human-readable reason phrase, handy for logging or debug output. */
    public function label(): string
    {
        return match ($this) {
            self::OK                  => 'OK',
            self::CREATED              => 'Created',
            self::NO_CONTENT           => 'No Content',
            self::BAD_REQUEST          => 'Bad Request',
            self::UNAUTHORIZED         => 'Unauthorized',
            self::FORBIDDEN            => 'Forbidden',
            self::NOT_FOUND            => 'Not Found',
            self::CONFLICT             => 'Conflict',
            self::UNPROCESSABLE_ENTITY => 'Unprocessable Entity',
            self::TOO_MANY_REQUESTS    => 'Too Many Requests',
            self::SERVER_ERROR         => 'Internal Server Error',
        };
    }

    public function isSuccess(): bool
    {
        return $this->value >= 200 && $this->value < 300;
    }
}
