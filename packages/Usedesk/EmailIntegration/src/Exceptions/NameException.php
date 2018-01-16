<?php

namespace Usedesk\EmailIntegration\Exceptions;

use Exception;

class NameException extends Exception
{
    public static function create(string $name, string $guardName = '')
    {
        return new static("There is no named `{$name}` for guard `{$guardName}`.");
    }
}