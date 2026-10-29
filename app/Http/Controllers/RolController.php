<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RolController extends Controller
{
    private const MODELO = Rol::class;

    // Cambié listar() por index(Request $request) y comenté la vista Blade para devolver los roles en JSON con formato estándar (success, message, data).
    public function index(Request $request)
    {
        $roles = Rol::query()->get()->map(fn (Rol $rol) => [
            'id' => $rol->id,
            'nombre' => $rol->nombre,
            'imagen' => $rol->imagen,
            'estado' => $rol->estado ? 'Activo' : 'Inactivo',
        ])->all();

        // return view('roles.listado', compact('roles'));
        return response()->json([
            'success' => true,
            'message' => 'Roles recuperados correctamente.',
            'data' => $roles,
        ], 200);
    }

    // Comenté vistaFormulario y vistaEdicion porque en la API ya no uso vistas Blade.
    // public function vistaFormulario() { return view('roles.formulario'); }

    // Cambié registrar() por store(Request $request) agregando validación formal, subida de imagen y respuesta JSON 201.
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => ['required', 'string', 'max:255', 'unique:roles,nombre'],
            'estado' => ['nullable'],
            'imagen' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,gif,bmp', 'max:10240'],
        ], [
            'nombre.required' => 'El nombre del rol es obligatorio.',
            'nombre.unique' => 'Ya existe otro rol con este nombre.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Errores de validación.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $datos = $validator->validated();
        unset($datos['imagen']);

        $datos['estado'] = $request->has('estado')
            ? $this->parsearEstado($request->input('estado'), true)
            : true;

        if ($request->hasFile('imagen')) {
            try {
                $datos['imagen'] = $this->subirImagenOpcional($request);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al subir la imagen: ' . $e->getMessage(),
                ], 500);
            }
        }

        $rol = Rol::create($datos);

        // return redirect()->route('roles.listar')->with('success', 'Rol guardado exitosamente.');
        return response()->json([
            'success' => true,
            'message' => 'Rol registrado exitosamente.',
            'data' => $rol,
        ], 201);
    }

    // public function vistaEdicion($id = 1)
    // {
    //     $rol = Rol::find($id) ?? Rol::first();
    //     return view('roles.edicion', ['registro' => $rol]);
    // }

    // Cambié vistaMostrar por show(Request $request, $id) validando si el ID existe (404) en vez de tomar Rol::first().
    public function show(Request $request, $id)
    {
        // $rol = Rol::find($id) ?? Rol::first();
        $rol = Rol::find($id);

        if (!$rol) {
            return response()->json([
                'success' => false,
                'message' => "El rol con ID [{$id}] no existe en la base de datos.",
            ], 404);
        }

        $registro = [
            'id' => $rol->id,
            'nombre' => $rol->nombre,
            'imagen' => $rol->imagen,
            'estado' => $rol->estado ? 'Activo' : 'Inactivo',
        ];

        // return view('roles.mostrar', ['registro' => $registro]);
        return response()->json([
            'success' => true,
            'message' => 'Detalles del rol recuperados correctamente.',
            'data' => $registro,
        ], 200);
    }

    // Cambié actualizar por update(Request $request, $id) usando 'sometimes' para soportar edición completa o parcial por PUT en Postman.
    public function update(Request $request, $id)
    {
        $rol = Rol::find($id);

        if (!$rol) {
            return response()->json([
                'success' => false,
                'message' => "No se puede actualizar: el rol con ID [{$id}] no existe en la base de datos.",
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nombre' => ['sometimes', 'required', 'string', 'max:255', 'unique:roles,nombre,' . $id],
            'estado' => ['nullable'],
            'imagen' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,gif,bmp', 'max:10240'],
        ], [
            'nombre.required' => 'El nombre del rol es obligatorio.',
            'nombre.unique' => 'Ya existe otro rol con este nombre.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Errores de validación.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $datos = $validator->validated();

        if ($request->has('estado')) {
            $datos['estado'] = $this->parsearEstado($request->input('estado'), (bool) $rol->estado);
        } else {
            unset($datos['estado']);
        }

        if ($request->hasFile('imagen')) {
            try {
                $datos['imagen'] = $this->subirImagenOpcional($request);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al subir la imagen: ' . $e->getMessage(),
                ], 500);
            }
        } else {
            unset($datos['imagen']);
        }

        $rol->update($datos);

        // return redirect()->route('roles.listar')->with('success', 'Rol actualizado exitosamente.');
        return response()->json([
            'success' => true,
            'message' => 'Rol actualizado exitosamente.',
            'data' => $rol,
        ], 200);
    }

    // Cambié borrar por destroy(Request $request, $id) aplicando borrado lógico en lugar de delete() físico porque administradores referencia a roles.
    public function destroy(Request $request, $id)
    {
        $rol = Rol::find($id);

        if (!$rol) {
            return response()->json([
                'success' => false,
                'message' => "No se puede eliminar: el rol con ID [{$id}] no existe en la base de datos.",
            ], 404);
        }

        // Antes intentaba $rol->delete(), lo cambié a borrado lógico con estado = false por la llave foránea en administradores.
        $rol->estado = false;
        $rol->save();

        // return redirect()->route('roles.listar')->with('success', 'Rol eliminado exitosamente.');
        return response()->json([
            'success' => true,
            'message' => "El rol '{$rol->nombre}' fue desactivado correctamente (borrado lógico).",
            'data' => $rol,
        ], 200);
    }
}
