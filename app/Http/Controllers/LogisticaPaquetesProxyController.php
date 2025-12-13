<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LogisticaPaquetesProxyController extends Controller
{
    /**
     * GET /api/gateway/logistica/paquetes/pendientes
     * Proxy a MS Logística: GET /api/paquetes/pendientes
     */
    public function pendientes(Request $request)
    {
        $baseUrl = rtrim(env('MS_LOGISTICA_URL', ''), '/');

        if (!$baseUrl) {
            return response()->json([
                'success' => false,
                'error'   => 'MS_LOGISTICA_URL no está configurado',
            ], 500);
        }

        $targetUrl = $baseUrl . '/api/paquetes/pendientes';
        \Log::info('Gateway → Logistica URL objetivo', [
            'base'  => $baseUrl,
            'full'  => $targetUrl,
        ]);
        $started   = microtime(true);

        try {
            $response = Http::timeout(10)
                ->withHeaders($this->forwardedHeaders($request))
                ->get($targetUrl, $request->query());

            $this->storeLog('pendientes', $request, $response, $targetUrl, $started);

            return response($response->body(), $response->status())
                ->header('Content-Type', $response->header('Content-Type', 'application/json'));

        } catch (\Throwable $e) {
            $this->storeException('pendientes', $request, $e, $targetUrl, $started);

            return response()->json([
                'success' => false,
                'error'   => 'Error al llamar a Logística',
            ], 502);
        }
    }

    public function armar(Request $request, int $id)
    {
        $baseUrl = rtrim(env('MS_LOGISTICA_URL', ''), '/');

        if (!$baseUrl) {
            return response()->json([
                'success' => false,
                'error'   => 'MS_LOGISTICA_URL no está configurado',
            ], 500);
        }

        $targetUrl = $baseUrl . "/api/paquetes/{$id}/armar";
        $started   = microtime(true);

        try {
            $response = Http::timeout(10)
                ->withHeaders($this->forwardedHeaders($request))
                ->patch($targetUrl, $request->all());

            $this->storeLog('armar', $request, $response, $targetUrl, $started);

            return response($response->body(), $response->status())
                ->header('Content-Type', $response->header('Content-Type', 'application/json'));

        } catch (\Throwable $e) {
            $this->storeException('armar', $request, $e, $targetUrl, $started);

            return response()->json([
                'success' => false,
                'error'   => 'Error al llamar a Logística',
            ], 502);
        }
    }


    public function destinoVoluntario(Request $request, string $codigo)
    {
        $baseUrl = rtrim(env('MS_LOGISTICA_URL', ''), '/');

        if (!$baseUrl) {
            return response()->json([
                'success' => false,
                'error'   => 'MS_LOGISTICA_URL no está configurado',
            ], 500);
        }

        $encoded   = urlencode($codigo);
        $targetUrl = $baseUrl . "/api/paquetes/destino-voluntario/{$encoded}";
        $started   = microtime(true);

        try {
            $response = Http::timeout(10)
                ->withHeaders($this->forwardedHeaders($request))
                ->get($targetUrl, $request->query());

            $this->storeLog('destino_voluntario', $request, $response, $targetUrl, $started);

            return response($response->body(), $response->status())
                ->header('Content-Type', $response->header('Content-Type', 'application/json'));

        } catch (\Throwable $e) {
            $this->storeException('destino_voluntario', $request, $e, $targetUrl, $started);

            return response()->json([
                'success' => false,
                'error'   => 'Error al llamar a Logística',
            ], 502);
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
    //LOG PARA MOSTRAR POR SI ACASO
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

        $cacheKey = "gateway:logistica:paquetes:{$operation}";
        $logs     = Cache::get($cacheKey, []);

        $logs[] = $entry;
        Cache::put($cacheKey, $logs, now()->addDay());

        Log::info('Gateway Logistica proxy', $entry);
    }

    protected function storeException(
        string  $operation,
        Request $request,
        \Throwable $e,
        string  $targetUrl,
        float   $startedAt
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

        $cacheKey = "gateway:logistica:paquetes:{$operation}";
        $logs     = Cache::get($cacheKey, []);
        $logs[]   = $entry;

        Cache::put($cacheKey, $logs, now()->addDay());

        Log::error('Gateway Logistica proxy error', $entry);
    }
}
