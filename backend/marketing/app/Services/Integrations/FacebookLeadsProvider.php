<?php

namespace App\Services\Integrations;

class FacebookLeadsProvider extends MetaLeadsProvider
{
    public function key(): string
    {
        return 'facebook';
    }

    public function label(): string
    {
        return 'Facebook Lead Ads';
    }

    public function description(): string
    {
        return 'People who filled in a Facebook lead form (Lead Ads). Each connection reads one form.';
    }
}
