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
        'prediccion' => 'MS_PREDICCIONES_URL',
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

    public function fetchByProvincia(string $provincia)//INCENDIOS, ANIMALES, LOGISTICA, PREDICCION
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

            $baseUrl = rtrim($url, '/');
            $encodedProvincia = rawurlencode($provincia);
            $segment = $name === 'prediccion'
                ? 'ubicacion'
                : 'provincia';

            $fullUrl = "{$baseUrl}/api/trazabilidad/{$segment}/{$encodedProvincia}";

            try {
                $response = Http::timeout(5)->get($fullUrl);

                $results[$name] = $response->successful()
                    ? $response->json()
                    : [
                        'error'  => "Status {$response->status()}",
                        'url'    => $fullUrl,
                        'segment'=> $segment,
                    ];
            } catch (\Throwable $e) {
                $results[$name] = [
                    'error' => $e->getMessage(),
                    'url'   => $fullUrl,
                ];
            }
        }

        return $results;
    }

    public function fetchAnimalesPorEspecie(string $especie): array
    {
        $url = env('MS_ANIMALES_URL');

        if (!$url) {
            return [
                'error' => "RUTA PARA MS_ANIMALES_URL NO EXISTE",
            ];
        }

        $baseUrl = rtrim($url, '/');
        $encoded = rawurlencode($especie);

        try {
            $response = Http::timeout(10)
                ->get("{$baseUrl}/api/trazabilidad/animales/especie/{$encoded}");

            return $response->successful()
                ? $response->json()
                : ['error' => "Status {$response->status()}"];
        } catch (\Throwable $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }

    public function fetchAnimalesLiberados(): array
    {
        $url = env('MS_ANIMALES_URL');

        if (!$url) {
            return [
                'error' => "RUTA PARA MS_ANIMALES_URL NO EXISTE",
            ];
        }

        $baseUrl = rtrim($url, '/');

        try {
            $response = Http::timeout(10)
                ->get("{$baseUrl}/api/trazabilidad/animales/liberados");

            return $response->successful()
                ? $response->json()
                : ['error' => "Status {$response->status()}"];
        } catch (\Throwable $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }

    public function fetchFirstSimpleIdentityByCi(string $ci): array
    {
    $priorityServices = [
        'logistica'        => 'MS_LOGISTICA_URL',
        'donaciones'       => 'MS_DONACIONES_URL',
        'voluntarios_post' => 'MS_VOLUNTARIOS_POST_URL',
        'brigadas'         => 'MS_BRIGADAS_URL',
        'animales'         => 'MS_ANIMALES_URL',
        'predicciones'     => 'MS_PREDICCIONES_URL',
    ];

    $attempts = [];

    foreach ($priorityServices as $name => $envVar) {
        $url = env($envVar);

        if (!$url) {
            $attempts[] = [
                'system'  => $name,
                'status'  => 'env_missing',
                'message' => "RUTA PARA $envVar NO EXISTE",
            ];
            continue;
        }

        try {
            $response = Http::timeout(5)
                ->get("$url/api/registro/ci/" . urlencode($ci));

            $attempts[] = [
                'system' => $name,
                'status' => $response->status(),
            ];

            if (!$response->successful()) {
                continue;
            }

            $body = $response->json();
            $persona = $body['persona'] ?? $body['data'] ?? null;

            if (!empty($body['success']) && !empty($body['found']) && is_array($persona)) {
                return [
                    'success'  => true,
                    'found'    => true,
                    'system'   => $name,
                    'persona'  => [
                        'nombre'   => $persona['nombre']   ?? null,
                        'apellido' => $persona['apellido'] ?? null,
                        'telefono' => $persona['telefono'] ?? null,
                    ],
                    'attempts' => $attempts,
                ];
            }

        } catch (\Throwable $e) {
            $attempts[] = [
                'system'  => $name,
                'status'  => 'exception',
                'message' => $e->getMessage(),
            ];
            continue;
        }
    }

    return [
        'success'  => true,
        'found'    => false,
        'system'   => null,
        'persona'  => [
            'nombre'   => null,
            'apellido' => null,
            'telefono' => null,
        ],
        'attempts' => $attempts,
    ];
}

   
   
   public function fetchAllCisAcrossServices(): array
    {
        $results = [];
        $seen = [];

        foreach ($this->services as $serviceName => $envVar) {
            $url = env($envVar);

            if (!$url) {
                logger()->warning("RUTA PARA {$envVar} NO EXISTE");
                continue;
            }

            $fullUrl = rtrim($url, '/') . '/api/users/ci';

            try {
                $response = Http::timeout(10)->get($fullUrl);

                if (!$response->successful()) {
                    logger()->warning("CI list failed on {$serviceName} ({$response->status()}) {$fullUrl}");
                    continue;
                }

                $data = $response->json();

                $lista = [];
                if (is_array($data) && isset($data['lista_ci']) && is_array($data['lista_ci'])) {
                    $lista = $data['lista_ci'];
                } elseif (is_array($data)) {
                    $lista = $data;
                }

                foreach ($lista as $ci) {
                    $ciKey = trim((string)$ci);
                    if ($ciKey === '') continue;

                    if (!isset($seen[$ciKey])) {
                        $seen[$ciKey] = true;
                        $results[] = $ciKey;
                    }
                }
            } catch (\Throwable $e) {
                logger()->warning("Busqueda de CI fallo en {$serviceName}: {$e->getMessage()} | URL: {$fullUrl}");
                continue;
            }
        }

        return $results;
    }

}
