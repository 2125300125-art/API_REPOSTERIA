<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GeolocalizacionController extends Controller
{
    // Actualicé obtenerUbicacion comentando el retorno a la vista Blade para devolver los datos de geolocalización en JSON.
    public function obtenerUbicacion(Request $request)
    {
        $ip = $request->ip();
        // $respuesta = Http::get("http://ip-api.com/json/{$ip}");
        $respuesta = Http::get("http://ip-api.com/json/1.178.192.128");

        if ($respuesta->failed()) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo obtener la ubicación.',
            ], 500);
        }

        // return view('geolocalizacion.ipublicacion', compact('respuesta'));
        return response()->json([
            'success' => true,
            'message' => 'Ubicación obtenida correctamente.',
            'data' => $respuesta->json(),
        ], 200);
    }
}

