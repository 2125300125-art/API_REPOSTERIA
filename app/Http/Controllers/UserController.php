<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    private const MODELO = User::class;

    // Comenté el arreglo de prueba que usaba con sesiones temporales para trabajar directamente con la tabla users en MySQL.
    // private array $datos = [
    //     ['id' => 1, 'name' => 'Usuario de prueba', 'email' => 'usuario@ejemplo.test'],
    // ];

    // Cambié listar() por index(Request $request) comentando la vista Blade para devolver el listado de usuarios en JSON.
    public function index(Request $request)
    {
        $users = User::query()->get(['id', 'name', 'email', 'created_at', 'updated_at'])->map(fn (User $user) => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ])->all();

        // return view('users.listado', compact('users'));
        return response()->json([
            'success' => true,
            'message' => 'Usuarios recuperados correctamente.',
            'data' => $users,
        ], 200);
    }

    // Comenté vistaFormulario y vistaEdicion porque en la API REST ya no utilizo vistas Blade.
    // public function vistaFormulario()
    // {
    //     return view('users.formulario');
    // }

    // Cambié registrar() por store(Request $request) guardando en la tabla users con contraseña encriptada y respuesta JSON 201.
    public function store(Request $request)
    {
        // $this->registrarDatoTemporal('users', $this->datos, $request);
        // return redirect()->route('users.listar')->with('success', 'Registro agregado solo para esta sesión.');

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.unique' => 'Ya existe un usuario registrado con este correo.',
            'password.required' => 'La contraseña es obligatoria.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Errores de validación.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $datos = $validator->validated();
        $datos['password'] = Hash::make($datos['password']);

        $user = User::create($datos);

        return response()->json([
            'success' => true,
            'message' => 'Usuario registrado exitosamente.',
            'data' => $user,
        ], 201);
    }

    // public function vistaEdicion($id = 1)
    // {
    //     $registro = $this->buscarDatoTemporal('users', $this->datos, (int) $id);
    //     return view('users.edicion', ['registro' => $registro]);
    // }

    // Cambié vistaMostrar() por show(Request $request, $id) consultando el usuario en MySQL y respondiendo JSON.
    public function show(Request $request, $id)
    {
        // $registro = $this->buscarDatoTemporal('users', $this->datos, (int) $id);
        // return view('users.mostrar', ['registro' => $registro]);

        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => "El usuario con ID [{$id}] no existe en la base de datos.",
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detalles del usuario recuperados correctamente.',
            'data' => $user,
        ], 200);
    }

    // Cambié actualizar() por update(Request $request, $id) con regla 'sometimes' para soportar actualizaciones por PUT desde Postman.
    public function update(Request $request, $id)
    {
        // $this->actualizarDatoTemporal('users', $this->datos, $request, (int) $id);
        // return redirect()->route('users.listar')->with('success', 'Registro actualizado solo para esta sesión.');

        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => "No se puede actualizar: el usuario con ID [{$id}] no existe en la base de datos.",
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', 'unique:users,email,' . $id],
            'password' => ['nullable', 'string', 'min:8'],
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.unique' => 'Ya existe otro usuario con este correo.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Errores de validación.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $datos = $validator->validated();

        if ($request->filled('password')) {
            $datos['password'] = Hash::make($request->input('password'));
        } else {
            unset($datos['password']);
        }

        $user->update($datos);

        return response()->json([
            'success' => true,
            'message' => 'Usuario actualizado exitosamente.',
            'data' => $user,
        ], 200);
    }

    // Cambié borrar() por destroy(Request $request, $id) eliminando el registro en MySQL y respondiendo JSON.
    public function destroy(Request $request, $id)
    {
        // $this->borrarDatoTemporal('users', $this->datos, (int) $id);
        // return redirect()->route('users.listar')->with('success', 'Registro eliminado solo para esta sesión.');

        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => "No se puede eliminar: el usuario con ID [{$id}] no existe en la base de datos.",
            ], 404);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => "El usuario '{$user->name}' fue eliminado correctamente.",
        ], 200);
    }
}

