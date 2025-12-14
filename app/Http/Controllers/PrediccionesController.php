<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PrediccionesController extends Controller
{
    /**
     * GET /api/gateway/prediccion/weather?lat=...&lng=...
     * MS Predicciones: GET /api/public/weather?lat=...&lng=...
     */
    public function weather(Request $request)
    {
        $baseUrl = rtrim(env('MS_PREDICCIONES_URL', ''), '/');

        if (!$baseUrl) {
            return response()->json([
                'success' => false,
                'error'   => 'MS_PREDICCIONES_URL no está configurado',
            ], 500);
        }

        $lat = $request->query('latitude');
        $lng = $request->query('longitude');

        if ($lat === null || $lng === null) {
            return response()->json([
                'success' => false,
                'error'   => 'Parámetros "lat" y "lng" son requeridos',
            ], 422);
        }

        $targetUrl = $baseUrl . '/api/public/weather';
        $query     = [
            'latitude' => $lat,
            'longitude' => $lng,
        ];
        $started   = microtime(true);

        try {
            Log::info('Gateway Predicciones weather → MS', [
                'target_url' => $targetUrl,
                'query'      => $query,
            ]);

            $response = Http::timeout(10)
                ->withHeaders($this->forwardedHeaders($request))
                ->get($targetUrl, $query);

            $this->storeLog(
                'predicciones_weather',
                $request,
                $response,
                $targetUrl . '?' . http_build_query($query),
                $started
            );

            return response($response->body(), $response->status())
                ->header('Content-Type', $response->header('Content-Type', 'application/json'));

        } catch (\Throwable $e) {
            $this->storeException(
                'predicciones_weather',
                $request,
                $e,
                $targetUrl . '?' . http_build_query($query),
                $started
            );

            $payload = [
                'success' => false,
                'error'   => 'Error al llamar al microservicio de Predicciones',
            ];

            if (config('app.debug')) {
                $payload['debug'] = [
                    'target_url' => $targetUrl,
                    'query'      => $query,
                    'exception'  => $e->getMessage(),
                ];
            }

            return response()->json($payload, 502);
        }
    }

    /**
     * GET /api/gateway/prediccion/lookup?lat=...&lng=...&hours=6
     * MS Predicciones: GET /api/public/predictions/lookup?lat=...&lng=...&hours=...
     */
    public function lookup(Request $request)
    {
        $baseUrl = rtrim(env('MS_PREDICCIONES_URL', ''), '/');

        if (!$baseUrl) {
            return response()->json([
                'success' => false,
                'error'   => 'MS_PREDICCIONES_URL no está configurado',
            ], 500);
        }
        $lat   = $request->query('lat');
        $lng   = $request->query('lng');
        $hours = $request->query('hours');

        if ($lat === null || $lng === null || $hours === null) {
            return response()->json([
                'success' => false,
                'error'   => 'Parámetros "lat", "lng" y "hours" son requeridos',
            ], 422);
        }

        $targetUrl = $baseUrl . '/api/public/predictions/lookup';
        $query     = [
            'lat'   => $lat,
            'lng'   => $lng,
            'hours' => $hours,
        ];
        $started   = microtime(true);

        try {
            Log::info('Gateway Predicciones lookup → MS', [
                'target_url' => $targetUrl,
                'query'      => $query,
            ]);

            $response = Http::timeout(10)
                ->withHeaders($this->forwardedHeaders($request))
                ->get($targetUrl, $query);

            $this->storeLog(
                'predicciones_lookup',
                $request,
                $response,
                $targetUrl . '?' . http_build_query($query),
                $started
            );

            return response($response->body(), $response->status())
                ->header('Content-Type', $response->header('Content-Type', 'application/json'));

        } catch (\Throwable $e) {
            $this->storeException(
                'predicciones_lookup',
                $request,
                $e,
                $targetUrl . '?' . http_build_query($query),
                $started
            );

            $payload = [
                'success' => false,
                'error'   => 'Error al llamar al microservicio de Predicciones',
            ];

            if (config('app.debug')) {
                $payload['debug'] = [
                    'target_url' => $targetUrl,
                    'query'      => $query,
                    'exception'  => $e->getMessage(),
                ];
            }

            return response()->json($payload, 502);
        }
    }

    protected function forwardedHeaders(Request $request): array
    {
        $headers = [
            'Accept' => 'application/json',
        ];

        if ($request->hasHeader('X-Client-System')) {
            $headers['X-Client-System'] = $request->header('X-Client-System');
        }

        if ($request->hasHeader('Authorization')) {
            $headers['Authorization'] = $request->header('Authorization');
        }

        return $headers;
    }

    protected function storeLog(
        string  $operation,
        Request $request,
        $response,
        string  $targetUrl,
        float   $startedAt
    ): void {
        $durationMs = (int) ((microtime(true) - $startedAt) * 1000);

        $entry = [
            'ts'          => now()->toIso8601String(),
            'operation'   => $operation,
            'method'      => $request->method(),
            'status'      => $response->status(),
            'target_url'  => $targetUrl,
            'caller'      => $request->header('X-Client-System'),
            'ip'          => $request->ip(),
            'duration_ms' => $durationMs,
        ];

        $cacheKey = "gateway:predicciones:{$operation}";
        $logs     = Cache::get($cacheKey, []);

        $logs[] = $entry;
        Cache::put($cacheKey, $logs, now()->addDay());

        Log::info('Gateway Predicciones proxy', $entry);
    }

    protected function storeException(
        string    $operation,
        Request   $request,
        \Throwable $e,
        string    $targetUrl,
        float     $startedAt
    ): void {
        $durationMs = (int) ((microtime(true) - $startedAt) * 1000);

        $entry = [
            'ts'          => now()->toIso8601String(),
            'operation'   => $operation,
            'method'      => $request->method(),
            'status'      => 'exception',
            'error'       => $e->getMessage(),
            'target_url'  => $targetUrl,
            'caller'      => $request->header('X-Client-System'),
            'ip'          => $request->ip(),
            'duration_ms' => $durationMs,
        ];

        $cacheKey = "gateway:predicciones:{$operation}";
        $logs     = Cache::get($cacheKey, []);
        $logs[]   = $entry;

        Cache::put($cacheKey, $logs, now()->addDay());

        Log::error('Gateway Predicciones proxy error', $entry);
    }
}
