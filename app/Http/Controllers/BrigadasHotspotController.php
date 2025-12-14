<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BrigadasHotspotController extends Controller
{
    /**
     * GET /api/gateway/brigadas/hotspots

     * Proxy a MS Donaciones: GET /api/v1/hotspots/live
     */
    public function hotspots(Request $request)
    {
        $baseUrl = rtrim(env('MS_BRIGADAS_URL', ''), '/');

        if (!$baseUrl) {
            return response()->json([
                'success' => false,
                'error'   => 'MS_BRIGADAS_URL no está configurado',
            ], 500);
        }

        $targetUrl = $baseUrl . '/api/v1/hotspots/live';
        $started   = microtime(true);

        try {
            $response = Http::timeout(10)
                ->withHeaders($this->forwardedHeaders($request))
                ->get($targetUrl, $request->query());

            $this->storeLog('hotspots', $request, $response, $targetUrl, $started);

            return response($response->body(), $response->status())
                ->header('Content-Type', $response->header('Content-Type', 'application/json'));

        } catch (\Throwable $e) {
            $this->storeException('hotspots', $request, $e, $targetUrl, $started);

            return response()->json([
                'success' => false,
                'error'   => 'Error al llamar a Brigadas',
            ], 502);
        }
    }
    public function reportesAnimales(Request $request)
    {
        $baseUrl = rtrim(env('MS_BRIGADAS_URL', ''), '/');

        if (!$baseUrl) {
            return response()->json([
                'success' => false,
                'error'   => 'MS_BRIGADAS_URL no está configurado',
            ], 500);
        }

        $targetUrl = $baseUrl . '/api/v1/reportes-rescate-animales';
        $started   = microtime(true);

        try {
            $response = Http::timeout(10)
                ->withHeaders($this->forwardedHeaders($request))
                ->get($targetUrl, $request->query());

            $this->storeLog('reportes-para-brigadas', $request, $response, $targetUrl, $started);

            return response($response->body(), $response->status())
                ->header('Content-Type', $response->header('Content-Type', 'application/json'));

        } catch (\Throwable $e) {
            $this->storeException('reportes-para-brigadas', $request, $e, $targetUrl, $started);

            return response()->json([
                'success' => false,
                'error'   => 'Error al llamar a Brigadas',
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

        $cacheKey = "gateway:brigadas:releases:{$operation}";
        $logs     = Cache::get($cacheKey, []);

        $logs[] = $entry;
        Cache::put($cacheKey, $logs, now()->addDay());

        Log::info('Gateway Brigadas releases proxy', $entry);
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

        $cacheKey = "gateway:brigadas:releases:{$operation}";
        $logs     = Cache::get($cacheKey, []);
        $logs[]   = $entry;

        Cache::put($cacheKey, $logs, now()->addDay());

        Log::error('Gateway Brigadas releases proxy error', $entry);
    }
}
