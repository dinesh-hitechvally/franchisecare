<?php

namespace App\Services\Push;

use RuntimeException;

class PushException extends RuntimeException
{
    /** $gone: the service says this device no longer exists (the person revoked permission, or uninstalled the app): stop sending to it. */
    public function __construct(string $message, public bool $gone = false)
    {
        parent::__construct($message);
    }
}
