<?php

declare(strict_types=1);

namespace Dominasys\FilamentLocation\Enums;

enum PostalCodeLookupStatusEnum: string
{
    case FOUND = 'found';
    case INVALID = 'invalid';
    case NOT_FOUND = 'not_found';
    case UNSUPPORTED_COUNTRY = 'unsupported_country';
}
