<?php

namespace App\Http\Controllers;

use App\Models\Administrador;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    // Actualicé redirectToGoogle comentando el redirect directo del navegador para devolver en JSON la URL de autenticación con Google.
    public function redirectToGoogle(Request $request)
    {
        // return Socialite::driver('google')->redirect();

        try {
            $url = Socialite::driver('google')->stateless()->redirect()->getTargetUrl();

            return response()->json([
                'success' => true,
                'message' => 'URL de autenticación con Google generada correctamente.',
                'data' => [
                    'auth_url' => $url,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar la redirección a Google: ' . $e->getMessage(),
            ], 500);
        }
    }

    // Actualicé handleGoogleCallback comentando las redirecciones a vistas para retornar el administrador autenticado y su token en JSON.
    public function handleGoogleCallback(Request $request)
    {
        try {
            // $googleUser = Socialite::driver('google')->user();
            $googleUser = Socialite::driver('google')->stateless()->user();

            $admin = Administrador::with('rol')
                ->where('correo', $googleUser->getEmail())
                ->first();

            if (!$admin) {
                // return redirect()->route('login')->withErrors([
                //     'error' => 'El correo de Google no está registrado como administrador.'
                // ]);
                return response()->json([
                    'success' => false,
                    'message' => 'El correo de Google no está registrado como administrador.',
                ], 404);
            }

            if (!$admin->activo) {
                // return redirect()->route('login')->withErrors([
                //     'error' => 'Esta cuenta de administrador se encuentra desactivada.'
                // ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Esta cuenta de administrador se encuentra desactivada.',
                ], 403);
            }

            // Auth::guard('admin')->login($admin);
            // return redirect()->route('administradores.listar');

            $token = $admin->createToken('google-admin-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Autenticación con Google exitosa.',
                'token_type' => 'Bearer',
                'access_token' => $token,
                'data' => $admin,
            ], 200);
        } catch (\Exception $e) {
            // return redirect()->route('login')->withErrors([
            //     'error' => 'No se pudo iniciar sesión con Google.'
            // ]);
            return response()->json([
                'success' => false,
                'message' => 'No se pudo iniciar sesión con Google: ' . $e->getMessage(),
            ], 500);
        }
    }
}

