<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class DolarController extends Controller
{
    // Actualicé obtenerDolar comentando la vista Blade para devolver el tipo de cambio directamente en JSON.
    public function obtenerDolar(Request $request)
    {
        // 1. Petición a la API de DolarDOF
        $respuesta = Http::get('https://dolardof.com/api/v1/dof/today');

        if ($respuesta->failed()) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo obtener la tasa de cambio.',
            ], 500);
        }

        // 2. Convertimos el JSON a arreglo
        $datosDolar = $respuesta->json();

        // 3. Devolvemos la respuesta en JSON en lugar de enviar a la vista Blade
        // return view('geolocalizacion.cambio', compact('datosDolar'));
        return response()->json([
            'success' => true,
            'message' => 'Tasa de cambio obtenida correctamente.',
            'data' => $datosDolar,
        ], 200);
    }
}

