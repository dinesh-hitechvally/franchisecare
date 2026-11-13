<?php

namespace App\Services\Integrations;

class InstagramLeadsProvider extends MetaLeadsProvider
{
    public function key(): string
    {
        return 'instagram';
    }

    public function label(): string
    {
        return 'Instagram Lead Ads';
    }

    public function description(): string
    {
        return 'People who filled in an Instagram lead form (Lead Ads run on Instagram use Meta Instant Forms). Each connection reads one form.';
    }
}
