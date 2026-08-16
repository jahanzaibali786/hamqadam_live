<?php

declare(strict_types=1);

namespace App\Enums;

enum ApiErrorCode: string
{
    case BadRequest = 'bad_request';
    case Unauthenticated = 'unauthenticated';
    case Forbidden = 'forbidden';
    case NotFound = 'not_found';
    case Conflict = 'conflict';
    case ValidationFailed = 'validation_failed';
    case TooManyRequests = 'too_many_requests';
    case ServerError = 'server_error';
}

