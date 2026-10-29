<?php

namespace App\Http\Controllers;

use App\Models\Administrador;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class LoginController extends Controller
{
    // Comenté showLoginForm() porque en la API REST ya no muestro el formulario Blade de inicio de sesión.
    // /**
    //  * Muestra el formulario de inicio de sesión.
    //  */
    // public function showLoginForm()
    // {
    //     // Si el admin ya inició sesión, redirigir al panel
    //     if (Auth::guard('admin')->check()) {
    //         return redirect()->route('inicio');
    //     }
    //
    //     return view('auth.login');
    // }

    /**
     * Procesa el inicio de sesión del administrador para la API REST y genera su token Sanctum.
     */
    // Actualicé mi función login(Request $request) comentando las redirecciones y sesiones web anteriores
    // para validar credenciales en JSON y devolver un token Bearer con Laravel Sanctum para Postman.
    public function login(Request $request)
    {
        // --- Código anterior de login web con sesiones y guard 'admin' ---
        // $request->validate([
        //     'usuario' => 'required|string',
        //     'contraseña' => 'required|string',
        // ], [
        //     'usuario.required' => 'El campo usuario es obligatorio.',
        //     'contraseña.required' => 'El campo contraseña es obligatorio.',
        // ]);
        //
        // $admin = Administrador::where('usuario', $request->usuario)->first();
        //
        // if ($admin && !$admin->activo) {
        //     return back()->withErrors([
        //         'error' => 'Esta cuenta se encuentra desactivada.'
        //     ])->withInput($request->only('usuario'));
        // }
        //
        // $credenciales = [
        //     'usuario'  => $request->usuario,
        //     'password' => $request->contraseña,
        //     'activo'   => 1,
        // ];
        //
        // if (Auth::guard('admin')->attempt($credenciales)) {
        //     $request->session()->regenerate();
        //     return redirect()->intended(route('inicio'));
        // }
        //
        // return back()->withErrors([
        //     'error' => 'Credenciales incorrectas o cuenta inactiva.'
        // ])->withInput($request->only('usuario'));

        // 1. Validar que envíen usuario (o correo) y contraseña mediante Validator para responder siempre en JSON
        $validator = Validator::make($request->all(), [
            'usuario' => ['required_without:correo', 'nullable', 'string'],
            'correo' => ['required_without:usuario', 'nullable', 'email'],
            'contraseña' => ['required_without:password', 'nullable', 'string'],
            'password' => ['required_without:contraseña', 'nullable', 'string'],
        ], [
            'usuario.required_without' => 'El campo usuario es obligatorio.',
            'correo.required_without' => 'El campo correo o usuario es obligatorio.',
            'contraseña.required_without' => 'El campo contraseña es obligatorio.',
            'password.required_without' => 'El campo contraseña es obligatorio.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Errores de validación.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $identificador = $request->input('usuario') ?? $request->input('correo');
        $passwordPlano = $request->input('contraseña') ?? $request->input('password');

        // 2. Buscar al administrador por usuario o correo junto con su rol
        $admin = Administrador::with('rol')
            ->where('usuario', $identificador)
            ->orWhere('correo', $identificador)
            ->first();

        // 3. Verificar que exista el registro y que la contraseña coincida con el hash en MySQL
        if (!$admin || !Hash::check($passwordPlano, $admin->contraseña)) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciales incorrectas.',
            ], 401);
        }

        // 4. Verificar si la cuenta del administrador está activa
        if (!$admin->activo) {
            return response()->json([
                'success' => false,
                'message' => 'Esta cuenta se encuentra desactivada.',
            ], 403);
        }

        // 5. Generar el token de acceso personal con Sanctum y devolver los datos en JSON
        try {
            $token = $admin->createToken('admin-api-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Inicio de sesión exitoso.',
                'token_type' => 'Bearer',
                'access_token' => $token,
                'data' => [
                    'id' => $admin->id,
                    'nombre' => $admin->nombre,
                    'apellidos' => $admin->apellidos,
                    'correo' => $admin->correo,
                    'usuario' => $admin->usuario,
                    'rol_id' => $admin->rol_id,
                    'rol' => $admin->rol ? $admin->rol->nombre : 'Sin rol asignado',
                    'imagen' => $admin->imagen,
                    'estado' => $admin->activo ? 'Activo' : 'Inactivo',
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar el token de acceso: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Devuelve la información del administrador autenticado mediante su token Bearer.
     */
    public function perfil(Request $request)
    {
        $admin = $request->user();

        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado.',
            ], 401);
        }

        if (method_exists($admin, 'loadMissing')) {
            $admin->loadMissing('rol');
        }

        return response()->json([
            'success' => true,
            'message' => 'Perfil del administrador autenticado recuperado correctamente.',
            'data' => $admin,
        ], 200);
    }

    /**
     * Cierra la sesión del administrador revocando el token actual en la API REST.
     */
    // Actualicé logout(Request $request) comentando el cierre de sesión web para revocar el token de Sanctum y responder JSON.
    public function logout(Request $request)
    {
        // Auth::guard('admin')->logout();
        // $request->session()->invalidate();
        // $request->session()->regenerateToken();
        // return redirect()->route('login');

        $usuario = $request->user();
        $tokenActual = $usuario ? $usuario->currentAccessToken() : null;

        if ($tokenActual && method_exists($tokenActual, 'delete')) {
            $tokenActual->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada correctamente y token revocado.',
        ], 200);
    }
}
