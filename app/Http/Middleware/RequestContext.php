<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RequestContext
{
    public function handle(Request $request, Closure $next)
    {
        $requestId = (string) Str::uuid();

        $request->attributes->set('request_id', $requestId);

        $inicio = microtime(true);

        $response = $next($request);

        $duracao = round((microtime(true) - $inicio) * 1000, 2);

        $response->headers->set('X-Request-ID', $requestId);
        $response->headers->set('X-Response-Time', $duracao . 'ms');

        Log::info('http.request.completed', [
            'request_id' => $requestId,
            'method' => $request->method(),
            'route' => $request->route()?->uri() ?? $request->path(),
            'status' => $response->getStatusCode(),
            'duration_ms' => $duracao,
        ]);

        return $response;
    }
}