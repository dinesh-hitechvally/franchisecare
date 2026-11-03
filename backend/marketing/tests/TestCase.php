<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Testing\LoggedExceptionCollection;
use Tests\Support\EnvelopeTestResponse;

abstract class TestCase extends BaseTestCase
{
    /** Same as the framework's, but the response's json() / assertJson*() work on the payload inside the API's standard envelope. */
    protected function createTestResponse($response, $request)
    {
        return tap(EnvelopeTestResponse::fromBaseResponse($response, $request), function ($response) {
            $response->withExceptions(
                $this->app->bound(LoggedExceptionCollection::class)
                    ? $this->app->make(LoggedExceptionCollection::class)
                    : new LoggedExceptionCollection
            );
        });
    }
}
