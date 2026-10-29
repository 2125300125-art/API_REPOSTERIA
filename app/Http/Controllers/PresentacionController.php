<?php

namespace App\Http\Controllers;

use App\Models\Presentacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PresentacionController extends Controller
{
    private const MODELO = Presentacion::class;

    // Cambié listar() por index(Request $request) y comenté la vista Blade para retornar las presentaciones en JSON con success y data.
    public function index(Request $request)
    {
        $presentaciones = Presentacion::query()->get(['id', 'nombre', 'descripcion', 'imagen', 'estado'])->map(fn (Presentacion $presentacion) => [
            'id' => $presentacion->id,
            'nombre' => $presentacion->nombre,
            'descripcion' => $presentacion->descripcion,
            'imagen' => $presentacion->imagen,
            'estado' => $presentacion->estado ? 'Activa' : 'Inactiva',
        ])->all();

        // return view('presentaciones.listado', compact('presentaciones'));
        return response()->json([
            'success' => true,
            'message' => 'Presentaciones recuperadas correctamente.',
            'data' => $presentaciones,
        ], 200);
    }

    // Comenté los métodos de formularios web anteriores porque en la API uso directamente store() desde Postman.
    // public function vistaFormulario() { return $this->create(); }
    // public function create() { return view('presentaciones.create'); }
    // public function registrar(Request $request) { return $this->store($request); }

    // En store agregué subida opcional de imagen a ImgBB y cambié los redirects web por respuestas JSON.
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => ['required', 'string', 'max:255', 'unique:presentaciones,nombre'],
            'descripcion' => ['nullable', 'string', 'max:500'],
            'estado' => ['nullable'],
            'imagen' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,gif,bmp', 'max:10240'],
        ], [
            'nombre.required' => 'El nombre de la presentación es obligatorio.',
            'nombre.unique' => 'Ya existe otra presentación con este nombre.',
        ]);

        if ($validator->fails()) {
            // return redirect()->back()->withErrors($validator)->withInput();
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

        $presentacion = Presentacion::create($datos);

        // return redirect()->route('presentaciones.index')->with('mensaje', 'Registro guardado exitosamente.');
        return response()->json([
            'success' => true,
            'message' => 'Presentación registrada exitosamente.',
            'data' => $presentacion,
        ], 201);
    }

    // public function vistaEdicion($id = null)
    // {
    //     $presentacion = Presentacion::find($id);
    //     if (!$presentacion) { return redirect()->route('presentaciones.listar'); }
    //     return view('presentaciones.edicion', compact('presentacion'));
    // }

    // Cambié vistaMostrar por show(Request $request, $id) incluyendo descripción e imagen en la respuesta JSON.
    public function show(Request $request, $id)
    {
        // 1. Validar que el ID exista en la base de datos
        $presentacion = Presentacion::find($id);

        if (!$presentacion) {
            // return redirect()->route('presentaciones.listar')
            //     ->with('error', "La presentación con ID [{$id}] no existe en la base de datos.");
            return response()->json([
                'success' => false,
                'message' => "La presentación con ID [{$id}] no existe en la base de datos.",
            ], 404);
        }

        $registro = [
            'id' => $presentacion->id,
            'nombre' => $presentacion->nombre,
            'descripcion' => $presentacion->descripcion,
            'imagen' => $presentacion->imagen,
            'estado' => $presentacion->estado ? 'Activa' : 'Inactiva',
        ];

        // return view('presentaciones.mostrar', compact('presentacion', 'registro'));
        return response()->json([
            'success' => true,
            'message' => 'Detalles de la presentación recuperados correctamente.',
            'data' => $registro,
        ], 200);
    }

    // Cambié actualizar por update(Request $request, $id) usando 'sometimes', soporte de imagen y respuesta JSON para peticiones PUT.
    public function update(Request $request, $id)
    {
        // 1. Validar que el ID exista en la base de datos
        $presentacion = Presentacion::find($id);

        if (!$presentacion) {
            // return redirect()->route('presentaciones.listar')
            //     ->with('error', "No se puede actualizar: la presentación con ID [{$id}] no existe en la base de datos.");
            return response()->json([
                'success' => false,
                'message' => "No se puede actualizar: la presentación con ID [{$id}] no existe en la base de datos.",
            ], 404);
        }

        // 2. Validación de datos de entrada con Validator y regla 'sometimes'
        $validator = Validator::make($request->all(), [
            'nombre' => ['sometimes', 'required', 'string', 'max:255', 'unique:presentaciones,nombre,' . $id],
            'descripcion' => ['nullable', 'string', 'max:500'],
            'estado' => ['nullable'],
            'imagen' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,gif,bmp', 'max:10240'],
        ], [
            'nombre.required' => 'El nombre de la presentación es obligatorio.',
            'nombre.unique' => 'Ya existe otra presentación con este nombre.',
        ]);

        if ($validator->fails()) {
            // return redirect()->back()->withErrors($validator)->withInput();
            return response()->json([
                'success' => false,
                'message' => 'Errores de validación.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $datos = $validator->validated();

        if ($request->has('estado')) {
            $datos['estado'] = $this->parsearEstado($request->input('estado'), (bool) $presentacion->estado);
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

        $presentacion->update($datos);

        // return redirect()->route('presentaciones.listar')->with('success', 'Presentación actualizada exitosamente.');
        return response()->json([
            'success' => true,
            'message' => 'Presentación actualizada exitosamente.',
            'data' => $presentacion,
        ], 200);
    }

    // Cambié borrar por destroy(Request $request, $id) conservando el borrado lógico y respondiendo JSON.
    public function destroy(Request $request, $id)
    {
        // 1. Validar que el ID exista antes del proceso de borrado
        $presentacion = Presentacion::find($id);

        if (!$presentacion) {
            // return redirect()->route('presentaciones.listar')
            //     ->with('error', "No se puede eliminar: la presentación con ID [{$id}] no existe en la base de datos.");
            return response()->json([
                'success' => false,
                'message' => "No se puede eliminar: la presentación con ID [{$id}] no existe en la base de datos.",
            ], 404);
        }

        // 2. Borrado lógico: la tabla presentaciones tiene el campo booleano 'estado' y clave foránea restrictOnDelete en productos
        $presentacion->estado = false;
        $presentacion->save();

        // return redirect()->route('presentaciones.listar')->with('success', "La presentación '{$presentacion->nombre}' fue dada de baja (Inactiva) correctamente.");
        return response()->json([
            'success' => true,
            'message' => "La presentación '{$presentacion->nombre}' fue desactivada correctamente (borrado lógico).",
            'data' => $presentacion,
        ], 200);
    }
}
