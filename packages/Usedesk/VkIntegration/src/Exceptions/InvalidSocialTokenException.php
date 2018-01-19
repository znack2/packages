<?php

namespace Usedesk\FbIntegration\Exceptions;

use Exception;

class InvalidSocialTokenException extends \Exception
{
    public static function create(string $name, string $guardName = '')
    {
        return new static("There is no named `{$name}` for guard `{$guardName}`.");
    }
}