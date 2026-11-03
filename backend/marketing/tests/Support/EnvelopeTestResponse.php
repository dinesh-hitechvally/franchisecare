<?php

namespace Tests\Support;

use App\Support\ApiResponse;
use Illuminate\Testing\AssertableJsonString;
use Illuminate\Testing\TestResponse;

/**
 * The dashboard API answers in the standard shape ({ success, message, data, meta }). Tests care about what was sent back,
 * so json(), assertJsonPath() and friends see the payload inside it, exactly as a screen does after the frontend unwraps it.
 * To check the envelope itself, read the raw body: json_decode($response->getContent(), true) (see ApiResponseTest).
 */
class EnvelopeTestResponse extends TestResponse
{
    public function decodeResponseJson()
    {
        $decoded = parent::decodeResponseJson();
        $body = $decoded->json();

        if (is_array($body) && ApiResponse::isEnvelope($body) && $body['success'] === true) {
            $this->decodedResponseJson = new AssertableJsonString(json_encode(ApiResponse::unwrap($body)));
        }

        return $this->decodedResponseJson;
    }
}
