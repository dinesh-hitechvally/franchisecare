<?php

namespace App\Services\Integrations;

use RuntimeException;

/** A problem talking to a platform, worded for the person who has to fix it (shown as is in the app). */
class IntegrationException extends RuntimeException {}
