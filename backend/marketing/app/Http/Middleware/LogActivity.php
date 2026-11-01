<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** Records every successful change (POST/PUT/PATCH/DELETE) made by a signed-in user. Request bodies are never stored. */
class LogActivity
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $user = $request->user();
        if ($user && ! $request->isMethodSafe() && $response->getStatusCode() < 400 && ! $request->is('api/logout')) {
            [$action, $description] = $this->describe($request);
            ActivityLog::create([
                'company_id' => $user->company_id, 'user_id' => $user->id, 'user_name' => $user->name,
                'action' => $action, 'description' => $description, 'ip' => $request->ip(),
            ]);
        }

        return $response;
    }

    protected function describe(Request $request): array
    {
        $route = $request->route();
        $name = (string) $route?->getName();
        $params = array_filter($route?->parameters() ?? [], fn ($p) => is_object($p));
        $model = $params ? array_values($params)[0] : null;
        $id = $model?->getKey();

        if (str_contains($name, '.')) {
            [$resource, $verb] = explode('.', $name, 2);
            $noun = str_replace('-', ' ', Str::singular($resource));
            $past = ['store' => 'created', 'update' => 'updated', 'destroy' => 'deleted'][$verb] ?? $verb;
            $label = $model ? ($model->name ?? $model->email ?? null) : null;

            return ["{$noun}.{$past}", ucfirst("{$past} {$noun}").($label ? " “{$label}”" : ($id ? " #{$id}" : ''))];
        }

        // non-resource routes, e.g. POST campaigns/{campaign}/send
        $uri = preg_replace('#^api/#', '', $route?->uri() ?? $request->path());
        $segments = explode('/', $uri);
        $verb = end($segments);
        $noun = str_replace('-', ' ', Str::singular($segments[0]));

        return ["{$noun}.{$verb}", ucfirst("{$verb} {$noun}").($id ? " #{$id}" : '')];
    }
}
