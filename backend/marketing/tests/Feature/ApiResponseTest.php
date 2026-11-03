<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Company;
use App\Support\ApiResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ApiResponseTest extends TestCase
{
    use RefreshDatabase;

    protected function owner(string $email = 'o@x.com', string $co = 'Acme'): array
    {
        $this->app['auth']->forgetGuards();
        $token = $this->postJson('/api/register', [
            'company_name' => $co, 'name' => 'Owner', 'email' => $email, 'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertOk()->json('token');

        return ['Authorization' => 'Bearer '.$token, 'Accept' => 'application/json'];
    }

    /** The body exactly as it travels: the envelope, not the payload inside it. */
    protected function raw($response): array
    {
        return json_decode($response->getContent(), true);
    }

    // ------------------------------------------------------------------ success

    public function test_a_plain_list_and_a_created_record_are_wrapped_in_the_success_shape(): void
    {
        $h = $this->owner();

        $created = $this->postJson('/api/tags', ['name' => 'VIP'], $h)->assertCreated();
        $body = $this->raw($created);
        $this->assertSame(['success', 'message', 'data'], array_keys($body));
        $this->assertTrue($body['success']);
        $this->assertNull($body['message']);
        $this->assertSame('VIP', $body['data']['name']);

        $list = $this->raw($this->getJson('/api/tags', $h)->assertOk());
        $this->assertTrue($list['success']);
        $this->assertSame('VIP', $list['data'][0]['name']); // the list itself is `data`
    }

    public function test_a_paginated_list_has_its_items_as_data_and_the_paging_as_meta(): void
    {
        $h = $this->owner();
        foreach (['a', 'b', 'c'] as $n) {
            $this->postJson('/api/people', ['email' => "$n@x.com"], $h)->assertCreated();
        }

        $body = $this->raw($this->getJson('/api/people?per_page=2', $h)->assertOk());
        $this->assertTrue($body['success']);
        $this->assertCount(2, $body['data']);                       // the items, not a paginator inside data
        $this->assertArrayNotHasKey('current_page', $body['data']);
        $this->assertSame([1, 2, 3], [$body['meta']['current_page'], $body['meta']['last_page'], $body['meta']['total']]);
        $this->assertSame(2, $body['meta']['per_page']);
        $this->assertArrayNotHasKey('data', $body['meta']);

        $this->getJson('/api/people?per_page=2', $h)->assertJsonPath('total', 3)->assertJsonCount(2, 'data'); // what the screens (and the tests) see: the original paginator
    }

    public function test_an_empty_answer_stays_empty(): void
    {
        $h = $this->owner();
        $id = $this->postJson('/api/tags', ['name' => 'VIP'], $h)->assertCreated()->json('id');

        $res = $this->deleteJson("/api/tags/$id", [], $h)->assertNoContent();
        $this->assertSame('', $res->getContent());
    }

    public function test_the_helpers_choose_the_wording_and_the_status_and_are_not_wrapped_twice(): void
    {
        $h = $this->owner();
        $id = $this->postJson('/api/campaigns', ['name' => 'Spring', 'type' => 'email', 'subject' => 'Hi', 'body' => '<p>x</p>'], $h)->assertCreated()->json('id');
        Campaign::withoutGlobalScopes()->whereKey($id)->update(['status' => 'sending']);

        $res = $this->postJson("/api/campaigns/$id/cancel", [], $h)->assertOk(); // answers with ApiResponse::ok($campaign, 'Sending stopped...')
        $body = $this->raw($res);
        $this->assertSame('Sending stopped. What already went out stays sent; the rest was skipped.', $body['message']);
        $this->assertSame('cancelled', $body['data']['status']);
        $this->assertArrayNotHasKey('success', $body['data']);        // one envelope, not an envelope inside an envelope
        $this->assertFalse($res->headers->has(ApiResponse::MARK));     // the internal marker never reaches the client
    }

    public function test_the_service_builds_every_kind_of_answer(): void
    {
        $shape = fn ($res) => [$res->getStatusCode(), $res->getData(true)];

        [$s, $b] = $shape(ApiResponse::ok(['a' => 1], 'Saved.'));
        $this->assertSame([200, ['success' => true, 'message' => 'Saved.', 'data' => ['a' => 1]]], [$s, $b]);

        [$s, $b] = $shape(ApiResponse::created(['id' => 5]));
        $this->assertSame([201, true, 5], [$s, $b['success'], $b['data']['id']]);

        [$s, $b] = $shape(ApiResponse::message('Test sent.'));
        $this->assertSame([200, 'Test sent.', null], [$s, $b['message'], $b['data']]);

        $this->assertSame(202, ApiResponse::accepted()->getStatusCode());
        $this->assertSame(204, ApiResponse::noContent()->getStatusCode());

        [$s, $b] = $shape(ApiResponse::paginated(new LengthAwarePaginator([['id' => 1], ['id' => 2]], 5, 2, 1)));
        $this->assertSame([200, [['id' => 1], ['id' => 2]], 5, 2], [$s, $b['data'], $b['meta']['total'], $b['meta']['per_page']]);

        [$s, $b] = $shape(ApiResponse::error('Nope.', 418, 'teapot', null, ['hint' => 'Try tea.']));
        $this->assertSame([418, false, 'Nope.', 'teapot', 'Try tea.'], [$s, $b['success'], $b['message'], $b['code'], $b['hint']]);

        [$s, $b] = $shape(ApiResponse::validation(['email' => ['The email must be valid.', 'Another.']]));
        $this->assertSame([422, 'validation_failed', 'The email must be valid.', ['email' => ['The email must be valid.', 'Another.']]], [$s, $b['code'], $b['message'], $b['errors']]);

        foreach ([['unauthenticated', 401, 'unauthenticated'], ['forbidden', 403, 'forbidden'], ['notFound', 404, 'not_found'], ['serverError', 500, 'server_error']] as [$method, $status, $code]) {
            [$s, $b] = $shape(ApiResponse::$method());
            $this->assertSame([$status, $code, false], [$s, $b['code'], $b['success']], $method);
            $this->assertNotSame('', $b['message']);
        }
        [$s, $b] = $shape(ApiResponse::conflict('Already there.'));
        $this->assertSame([409, 'conflict'], [$s, $b['code']]);

        $res = ApiResponse::tooManyRequests(30);
        $this->assertSame([429, '30'], [$res->getStatusCode(), $res->headers->get('Retry-After')]);
        $this->assertSame(30, $res->getData(true)['retry_after']);

        // wrap and unwrap are each other's opposite
        foreach ([['id' => 1], [1, 2, 3], 'text', null] as $payload) {
            $this->assertSame($payload, ApiResponse::unwrap(ApiResponse::wrap($payload)));
        }
        $page = ['data' => [['id' => 1]], 'current_page' => 1, 'last_page' => 3, 'per_page' => 1, 'total' => 3];
        $this->assertSame($page, ApiResponse::unwrap(ApiResponse::wrap($page)));
    }

    // ------------------------------------------------------------------ failure

    public function test_every_failure_has_the_same_shape(): void
    {
        $h = $this->owner();

        // validation: the first message is the message, and the fields are listed
        $body = $this->raw($this->postJson('/api/tags', [], $h)->assertStatus(422));
        $this->assertFalse($body['success']);
        $this->assertSame('validation_failed', $body['code']);
        $this->assertArrayHasKey('name', $body['errors']);
        $this->assertSame($body['errors']['name'][0], $body['message']);

        // abort() with a sentence keeps its sentence
        $id = $this->postJson('/api/campaigns', ['name' => 'C', 'type' => 'email', 'subject' => 's', 'body' => '<p>x</p>'], $h)->json('id');
        $body = $this->raw($this->postJson("/api/campaigns/$id/cancel", [], $h)->assertStatus(422));
        $this->assertSame([false, 'Only a campaign that is sending can be stopped.', 'unprocessable'], [$body['success'], $body['message'], $body['code']]);

        // a record that does not exist: a plain sentence, never the model's class name
        $body = $this->raw($this->putJson('/api/tags/99999', ['name' => 'x'], $h)->assertNotFound());
        $this->assertSame([false, 'not_found', 'That record could not be found.'], [$body['success'], $body['code'], $body['message']]);

        // an address that does not exist
        $body = $this->raw($this->getJson('/api/nothing-here', $h)->assertNotFound());
        $this->assertSame(['not_found', 'That could not be found.'], [$body['code'], $body['message']]);

        // wrong method
        $body = $this->raw($this->deleteJson('/api/login')->assertStatus(405));
        $this->assertSame('method_not_allowed', $body['code']);
    }

    public function test_signing_in_is_required(): void
    {
        $body = $this->raw($this->getJson('/api/tags')->assertStatus(401));
        $this->assertSame([false, 'unauthenticated'], [$body['success'], $body['code']]);
        $this->assertSame('Please sign in to continue.', $body['message']); // a sentence a person can read, not "Unauthenticated."
    }

    public function test_too_many_requests_say_when_to_try_again(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/login', ['email' => 'x@x.com', 'password' => 'wrong'])->assertStatus(422);
        }
        $res = $this->postJson('/api/login', ['email' => 'x@x.com', 'password' => 'wrong'])->assertStatus(429);
        $body = $this->raw($res);
        $this->assertSame([false, 'too_many_requests'], [$body['success'], $body['code']]);
        $this->assertGreaterThan(0, $body['retry_after']);
        $this->assertNotNull($res->headers->get('Retry-After'));
        $this->assertStringContainsString('Try again in', $body['message']);
    }

    public function test_an_unexpected_error_never_shows_its_details_unless_debugging(): void
    {
        Route::middleware('api')->get('api/boom', fn () => throw new \RuntimeException('the database password is hunter2'));

        config(['app.debug' => false]);
        $res = $this->getJson('/api/boom')->assertStatus(500);
        $body = $this->raw($res);
        $this->assertSame([false, 'server_error'], [$body['success'], $body['code']]);
        $this->assertStringNotContainsString('hunter2', $res->getContent());
        $this->assertArrayNotHasKey('debug', $body);
        $this->assertStringContainsString('Something went wrong', $body['message']);

        config(['app.debug' => true]); // while developing, the details help
        $body = $this->raw($this->getJson('/api/boom')->assertStatus(500));
        $this->assertSame('the database password is hunter2', $body['message']);
        $this->assertSame(\RuntimeException::class, $body['debug']['exception']);
    }

    // ------------------------------------------------------------------ left alone

    public function test_the_public_api_hosted_forms_and_tracking_keep_their_own_shape(): void
    {
        $h = $this->owner();
        $key = $this->postJson('/api/api-keys', ['name' => 'Zapier'], $h)->assertCreated()->json('key');

        // public API v1: a promise to outside systems, so its successful answers are not wrapped
        $this->app['auth']->forgetGuards();
        $v1 = $this->raw($this->postJson('/api/v1/people', ['email' => 'api@x.com'], ['Authorization' => 'Bearer '.$key, 'Accept' => 'application/json'])->assertCreated());
        $this->assertFalse(ApiResponse::isEnvelope($v1));
        $this->assertSame('api@x.com', $v1['email']);

        // ...but a failure there still says what went wrong in the standard way
        $this->app['auth']->forgetGuards();
        $body = $this->raw($this->getJson('/api/v1/people?email=nobody@x.com', ['Authorization' => 'Bearer '.$key])->assertNotFound());
        $this->assertSame([false, 'not_found'], [$body['success'], $body['code']]);

        // a hosted form's own page script reads this answer
        $this->app['auth']->forgetGuards();
        $form = $this->postJson('/api/forms', ['name' => 'News', 'type' => 'newsletter', 'subscribe' => true, 'save_person' => true], $h)->assertCreated()->json();
        $this->app['auth']->forgetGuards();
        $sub = $this->raw($this->postJson('/api/public/forms/'.$form['slug'], ['email' => 'fan@x.com'])->assertOk());
        $this->assertFalse(ApiResponse::isEnvelope($sub));
    }
}
