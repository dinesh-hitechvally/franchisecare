<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Puts every successful JSON answer of the dashboard API into the standard shape (see App\Support\ApiResponse),
 * so a controller can simply return a model, a list or a paginator and still answer consistently.
 *
 * Left exactly as they are: answers already built with ApiResponse, empty answers (204), files, downloads and streams, and the
 * parts of the API other systems depend on (the public API v1, the hosted forms, tracking links, media files).
 */
class WrapApiResponse
{
    /** Parts of the API whose answers other systems read: their shape is a promise and must not change. */
    protected const UNTOUCHED = ['api/v1/*', 'api/public/*', 'api/t/*', 'api/m/*', 'api/unsubscribe/*'];

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // built with ApiResponse already: just take the marker off
        if ($response->headers->has(ApiResponse::MARK)) {
            $response->headers->remove(ApiResponse::MARK);

            return $response;
        }

        if ($request->is(...self::UNTOUCHED) || $response instanceof StreamedResponse || $response instanceof BinaryFileResponse
            || ! $response->isSuccessful() || $response->getStatusCode() === 204
            || ! str_contains((string) $response->headers->get('Content-Type'), 'json')) {
            return $response;
        }

        $body = json_decode((string) $response->getContent(), true);
        if (json_last_error() !== JSON_ERROR_NONE || ApiResponse::isEnvelope($body)) {
            return $response;
        }

        $wrapped = ApiResponse::wrap($body);
        $response instanceof JsonResponse ? $response->setData($wrapped) : $response->setContent(json_encode($wrapped));

        return $response;
    }
}
