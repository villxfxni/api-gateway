<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Traceability\MicroserviceClient;

class TrazabilidadController extends Controller
{
     public function getTraceabilityByCi(string $ci)
    { 
        $client = new MicroserviceClient($ci);

        return response()->json([
            'ci_voluntario' => $ci,
            'services'      => $client->fetchAllByVoluntario(),
        ]);
    }

    public function getTraceabilityByCodigo(string $codigo)// LOGISTICA Y DONACIOENS
    {
        $client = new MicroserviceClient(); 

        return response()->json([
            'codigo_paquete' => $codigo,
            'services'       => $client->fetchByCodigoPaquete($codigo),
        ]);
    }
    public function getTraceabilityByPlaca(string $placa)//SOLO LOGISTICA
    {
        $client = new MicroserviceClient(); 

        return response()->json([
            'placa' => $placa,
            'services' => $client->fetchByVehiculo($placa),
        ]);
    }
    public function getTraceabilityBySolicitante(string $ci_solicitante)//SOLO LOGISTICA
    {
        $client = new MicroserviceClient(); 

        return response()->json([
            'ci_solicitante' => $ci_solicitante,
            'services' => $client->fetchBySolicitante($ci_solicitante),
        ]);
    }

    public function getTraceabilityByProvincia(string $provincia)//BRIGADAS, ANIMALES, LOGISTICA
    {
        $client = new MicroserviceClient(); 

        return response()->json([
            'provincia' => $provincia,
            'services' => $client->fetchByProvincia($provincia),
        ]);
    }
    public function getAnimalesPorEspecie(string $especie) //SOLO RESCATE DE ANIMALES
    {
        $client = new MicroserviceClient();

        $animales = $client->fetchAnimalesPorEspecie($especie);

        return response()->json([
            'success'  => true,
            'tipo'     => 'animales_por_especie',
            'query'    => $especie,
            'services' => [
                'animales' => $animales,
            ],
        ]);
    }
    public function getAnimalesLiberados()//SOLO RESCATE DE ANIMALES
    {
        $client = new MicroserviceClient();

        $animales = $client->fetchAnimalesLiberados();

        return response()->json([
            'success'  => true,
            'tipo'     => 'animales_liberados',
            'services' => [
                'animales' => $animales,
            ],
        ]);
    }
    public function getAllCisMerged()
    {
        $client = new MicroserviceClient();

        $cis = $client->fetchAllCisAcrossServices();

        return response()->json([
            'success' => true,
            'total'   => count($cis),
            'lista_ci'=> $cis,
        ]);
    }


    
}
