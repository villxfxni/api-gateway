<?php
namespace App\Services\Traceability;

use Illuminate\Support\Facades\Http;

class MicroserviceClient
{
    protected ?string $ci;

    protected $services = [
        'donaciones' => 'MS_DONACIONES_URL',
        'logistica'  => 'MS_LOGISTICA_URL',
        'brigadas'   => 'MS_BRIGADAS_URL', 
        'voluntarios_post' => 'MS_VOLUNTARIOS_POST_URL',
        'animales'   => 'MS_ANIMALES_URL',
        'prediccion' => 'MS_PREDICCION_URL',
    ];

    public function __construct(?string $ci = null)
    {
        $this->ci = $ci;
    }

    public function fetchAllByVoluntario()
    {
         if (!$this->ci) {
            return [
                'error' => 'ci_voluntario es obligatorio para esta operacion.',
            ];
        }
        $results = [];

        foreach ($this->services as $name => $envVar) {
            $url = env($envVar);

            if (!$url) {
                $results[$name] = [
                    'error' => "RUTA PARA $envVar NO EXISTE"
                ];
                continue;
            }

            try {
                $response = Http::timeout(10)
                    ->get("$url/api/trazabilidad/voluntario/{$this->ci}");

                $results[$name] = $response->successful()
                    ? $response->json()
                    : ['error' => "Status {$response->status()}"];
            } catch (\Throwable $e) {
                $results[$name] = [
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    public function fetchByCodigoPaquete(string $codigo)//LOGISTICA Y DONACIONES
    {
        $results = [];

        foreach ($this->services as $name => $envVar) {
            $url = env($envVar);

            if (!$url) {
                $results[$name] = [
                    'error' => "RUTA PARA $envVar NO EXISTE"
                ];
                continue;
            }

            try {
                $response = Http::timeout(5)
                    ->get("$url/api/trazabilidad/paquete/{$codigo}");

                $results[$name] = $response->successful()
                    ? $response->json()
                    : ['error' => "Status {$response->status()}"];
            } catch (\Throwable $e) {
                $results[$name] = [
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    public function fetchByVehiculo(string $placa): array //SOLO LOGISTICA
    {
        $results = [];

            $url = env('MS_LOGISTICA_URL');

            if (!$url) {
                $results[$url] = [
                    'error' => "RUTA PARA LOGISTICA NO EXISTE"
                ];
            }

            try {
                $response = Http::timeout(5)
                    ->get("$url/api/trazabilidad/vehiculo/{$placa}");

                $results['LOGISTICA'] = $response->successful()
                    ? $response->json()
                    : ['error' => "Status {$response->status()}"];
            } catch (\Throwable $e) {
                $results[$url] = [
                    'error' => $e->getMessage()
                ];
            }

        return $results;
    }

    public function fetchBySolicitante(string $ci_solicitante): array //SOLO LOGISTICA
    {
        $results = [];

            $url = env('MS_LOGISTICA_URL');

            if (!$url) {
                $results[$url] = [
                    'error' => "RUTA PARA LOGISTICA NO EXISTE"
                ];
            }

            try {
                $response = Http::timeout(5)
                    ->get("$url/api/trazabilidad/solicitante/{$ci_solicitante}");

                 $results['LOGISTICA'] = $response->successful()
                    ? $response->json()
                    : ['error' => "Status {$response->status()}"];
            } catch (\Throwable $e) {
                $results[$url] = [
                    'error' => $e->getMessage()
                ];
            }

        return $results;
    }

    public function fetchByProvincia(string $provincia)//INCENDIOS, ANIMALES, LOGISTICA
    {
        $results = [];

        foreach ($this->services as $name => $envVar) {
            $url = env($envVar);

            if (!$url) {
                $results[$name] = [
                    'error' => "RUTA PARA $envVar NO EXISTE"
                ];
                continue;
            }

            try {
                $response = Http::timeout(5)
                    ->get("$url/api/trazabilidad/provincia/{$provincia}");

                $results[$name] = $response->successful()
                    ? $response->json()
                    : ['error' => "Status {$response->status()}"];
            } catch (\Throwable $e) {
                $results[$name] = [
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }


}
