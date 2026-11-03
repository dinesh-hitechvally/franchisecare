<?php

namespace App\Support;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * One shape for everything the API says.
 *
 *   success:  { "success": true,  "message": "Saved." | null, "data": ..., "meta": { paging... } (lists only) }
 *   failure:  { "success": false, "message": "What went wrong, in words a person can read", "code": "validation_failed",
 *               "errors": { "field": ["..."] } (validation only) }
 *
 * Controllers can use the helpers below to choose the wording and status:
 *
 *   return ApiResponse::ok($campaign, 'Sending stopped.');
 *   return ApiResponse::created($form);
 *   return ApiResponse::paginated($query->paginate(25));
 *   return ApiResponse::error('That address is already used.', 422);
 *   return ApiResponse::notFound('That page no longer exists.');
 *
 * Controllers that just return a model, a list or a paginator are wrapped into the same success shape automatically
 * (App\Http\Middleware\WrapApiResponse), and every exception, including abort(), comes out as the failure shape
 * (see fromException and bootstrap/app.php), so the API is consistent without each action having to remember.
 */
class ApiResponse
{
    /** Set on responses built here, so the wrapping middleware knows they are finished. It is removed before sending. */
    public const MARK = 'X-Api-Envelope';

    // ------------------------------------------------------------------ success

    /** 200: the data (a model, a list, anything) and optionally a sentence for the person. */
    public static function ok(mixed $data = null, ?string $message = null, int $status = 200, ?array $meta = null): JsonResponse
    {
        return self::send(self::success($data, $message, $meta), $status);
    }

    /** 201: something was created. */
    public static function created(mixed $data = null, ?string $message = null): JsonResponse
    {
        return self::ok($data, $message, 201);
    }

    /** 202: accepted, and being worked on in the background. */
    public static function accepted(?string $message = 'Accepted. It is being processed.', mixed $data = null): JsonResponse
    {
        return self::ok($data, $message, 202);
    }

    /** 200 with a sentence and nothing else, e.g. "Test message sent." */
    public static function message(string $message, int $status = 200): JsonResponse
    {
        return self::ok(null, $message, $status);
    }

    /** 204: done, nothing to send back (after a delete). A 204 has no body, so there is nothing to wrap. */
    public static function noContent(): Response
    {
        return response()->noContent();
    }

    /** A page of a list: the items are `data`, the paging details are `meta`. */
    public static function paginated(AbstractPaginator $page, ?string $message = null): JsonResponse
    {
        $body = $page->toArray();
        $items = $body['data'];
        unset($body['data']);

        return self::ok($items, $message, 200, $body);
    }

    // ------------------------------------------------------------------ failure

    /** Any failure: what went wrong, the HTTP status, and a short machine-readable code the screen can switch on. */
    public static function error(string $message, int $status = 400, ?string $code = null, ?array $errors = null, array $extra = []): JsonResponse
    {
        $body = ['success' => false, 'message' => $message, 'code' => $code ?? self::codeFor($status)];
        if ($errors !== null) {
            $body['errors'] = $errors;
        }

        return self::send($body + $extra, $status);
    }

    /** 422: the form was filled in wrongly: `errors` maps each field to its messages. */
    public static function validation(array $errors, ?string $message = null): JsonResponse
    {
        return self::error($message ?? (array_values($errors)[0][0] ?? 'The given data was invalid.'), 422, 'validation_failed', $errors);
    }

    public static function unauthenticated(?string $message = null): JsonResponse
    {
        return self::error($message ?: 'Please sign in to continue.', 401);
    }

    public static function forbidden(?string $message = null): JsonResponse
    {
        return self::error($message ?: 'You do not have permission to do this.', 403);
    }

    public static function notFound(?string $message = null): JsonResponse
    {
        return self::error($message ?: 'That could not be found.', 404);
    }

    /** 409: it clashes with what is already there (a name or address that is taken, something already running). */
    public static function conflict(string $message): JsonResponse
    {
        return self::error($message, 409);
    }

    public static function tooManyRequests(?int $retryAfter = null): JsonResponse
    {
        return self::error(
            'Too many requests.'.($retryAfter ? " Try again in {$retryAfter} seconds." : ' Please slow down.'),
            429, null, null, $retryAfter ? ['retry_after' => $retryAfter] : [],
        )->withHeaders($retryAfter ? ['Retry-After' => $retryAfter] : []);
    }

    public static function serverError(?string $message = null): JsonResponse
    {
        return self::error($message ?: 'Something went wrong on our side. Please try again in a moment.', 500);
    }

    // ------------------------------------------------------------------ exceptions

    /** The failure response for any exception: used for every API request (bootstrap/app.php). */
    public static function fromException(Throwable $e): JsonResponse
    {
        if ($e instanceof ValidationException) {
            return self::validation($e->errors(), $e->getMessage());
        }
        if ($e instanceof AuthenticationException) {
            return self::unauthenticated($e->getMessage() === 'Unauthenticated.' ? null : $e->getMessage());
        }
        if ($e instanceof AuthorizationException) {
            return self::forbidden($e->getMessage());
        }
        if ($e instanceof ModelNotFoundException) {
            return self::notFound('That record could not be found.'); // never name the model class
        }
        if ($e instanceof MethodNotAllowedHttpException) {
            return self::error('That action is not allowed here.', 405)->withHeaders($e->getHeaders());
        }
        if ($e instanceof NotFoundHttpException && ($e->getMessage() === '' || str_contains($e->getMessage(), 'could not be found') || str_contains($e->getMessage(), 'No query results'))) {
            // a record that route binding could not find says so (never naming the model class); an unknown address stays general
            return self::notFound(str_contains($e->getMessage(), 'No query results') ? 'That record could not be found.' : null);
        }
        if ($e instanceof HttpExceptionInterface) {
            $status = $e->getStatusCode();
            if ($status === 429) {
                return self::tooManyRequests(isset($e->getHeaders()['Retry-After']) ? (int) $e->getHeaders()['Retry-After'] : null)->withHeaders($e->getHeaders());
            }

            // abort(422, 'a sentence') keeps its sentence; a bare abort(404) gets the standard one
            return self::error($e->getMessage() !== '' ? $e->getMessage() : self::sentenceFor($status), $status)->withHeaders($e->getHeaders());
        }

        // anything unexpected: a calm sentence for the person, the details only when debugging
        $debug = config('app.debug');
        $res = self::error($debug ? $e->getMessage() : self::sentenceFor(500), 500, 'server_error');
        if ($debug) {
            $res->setData($res->getData(true) + ['debug' => [
                'exception' => $e::class, 'file' => $e->getFile(), 'line' => $e->getLine(),
                'trace' => collect($e->getTrace())->take(8)->map(fn ($t) => ($t['file'] ?? '') . ':' . ($t['line'] ?? ''))->all(),
            ]]);
        }

        return $res;
    }

    // ------------------------------------------------------------------ the envelope itself

    /** The success shape (used by the helpers above, and by the middleware for plain returns). */
    public static function success(mixed $data = null, ?string $message = null, ?array $meta = null): array
    {
        $body = ['success' => true, 'message' => $message, 'data' => $data];
        if ($meta !== null) {
            $body['meta'] = $meta;
        }

        return $body;
    }

    /** Wraps what a controller returned plainly. A paginator's body is split into its items and its paging details. */
    public static function wrap(mixed $payload): array
    {
        if (is_array($payload) && array_key_exists('data', $payload) && array_key_exists('current_page', $payload) && array_key_exists('last_page', $payload)) {
            $items = $payload['data'];
            unset($payload['data']);

            return self::success($items, null, $payload);
        }

        return self::success($payload);
    }

    public static function isEnvelope(mixed $body): bool
    {
        return is_array($body) && array_key_exists('success', $body) && is_bool($body['success']) && array_key_exists('data', $body) && array_key_exists('message', $body);
    }

    /** The original payload back out of a success envelope (what the screens, and the tests, work with). */
    public static function unwrap(array $body): mixed
    {
        if (! self::isEnvelope($body) || $body['success'] !== true) {
            return $body;
        }

        return isset($body['meta']) ? ['data' => $body['data']] + $body['meta'] : $body['data'];
    }

    // ------------------------------------------------------------------ internals

    protected static function send(array $body, int $status): JsonResponse
    {
        return response()->json($body, $status, [self::MARK => '1']);
    }

    protected static function codeFor(int $status): string
    {
        return [
            400 => 'bad_request', 401 => 'unauthenticated', 402 => 'payment_required', 403 => 'forbidden', 404 => 'not_found', 405 => 'method_not_allowed',
            409 => 'conflict', 410 => 'gone', 413 => 'too_large', 419 => 'session_expired', 422 => 'unprocessable', 429 => 'too_many_requests', 503 => 'unavailable',
        ][$status] ?? ($status >= 500 ? 'server_error' : 'error');
    }

    protected static function sentenceFor(int $status): string
    {
        return [
            400 => 'That request could not be understood.', 401 => 'Please sign in to continue.', 403 => 'You do not have permission to do this.',
            404 => 'That could not be found.', 405 => 'That action is not allowed here.', 409 => 'That clashes with something that already exists.',
            413 => 'That is too large.', 419 => 'Your session has expired. Please reload the page.', 422 => 'The given data was invalid.',
            503 => 'This is unavailable right now. Please try again in a moment.',
        ][$status] ?? ($status >= 500 ? 'Something went wrong on our side. Please try again in a moment.' : 'That did not work.');
    }
}
