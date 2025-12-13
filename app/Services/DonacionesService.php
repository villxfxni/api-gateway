<?php

namespace App\Services\Traceability;

use Illuminate\Support\Facades\Http;

class DonacionesService
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(env('MS_DONACIONES_URL'), '/');
    }
    public function getTrazabilidad(string $ci): array
    {
        try {
            $response = Http::timeout(5)
                ->get("{$this->baseUrl}/api/trazabilidad/{$ci}");

            if ($response->successful()) {
                return [
                    'service' => 'logistica',
                    'status'  => 'ok',
                    'data'    => $response->json(),
                ];
            }

            return [
                'service' => 'logistica',
                'status'  => 'error',
                'message' => "HTTP {$response->status()}",
            ];

        } catch (\Throwable $e) {

            return [
                'service' => 'logistica',
                'status'  => 'unreachable',
                'message' => $e->getMessage(),
            ];
        }
    }
}
