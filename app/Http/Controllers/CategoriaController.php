<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoriaController extends Controller
{
    private const MODELO = Categoria::class;

    // Comenté los datos de prueba anteriores porque ya obtengo las categorías directo de la base de datos.
    // private array $datos = [
    //     ['id' => 1, 'nombre' => 'Agua purificada', 'descripcion' => 'Agua para consumo humano', 'productos' => 8, 'estado' => 'Activa'],
    //     ['id' => 2, 'nombre' => 'Accesorios', 'descripcion' => 'Bombas y soportes para garrafón', 'productos' => 3, 'estado' => 'Activa'],
    // ];

    // Cambié listar() por index(Request $request), agregué el campo imagen en la consulta y comenté la vista Blade para devolver JSON con success y data.
    public function index(Request $request)
    {
        $categorias = Categoria::query()->get(['id', 'nombre', 'imagen', 'estado'])->map(fn (Categoria $categoria) => [
            'id' => $categoria->id,
            'nombre' => $categoria->nombre,
            'imagen' => $categoria->imagen,
            'estado' => $categoria->estado ? 'Activa' : 'Inactiva',
        ])->all();

        // return view('categorias.listado', compact('categorias'));
        return response()->json([
            'success' => true,
            'message' => 'Categorías recuperadas correctamente.',
            'data' => $categorias,
        ], 200);
    }

    // Comenté los métodos de formularios web porque ahora envío los datos por Postman.
    // public function vistaFormulario() { return $this->create(); }
    // public function create() { return view('categorias.create'); }
    // public function registrar(Request $request) { return $this->store($request); }

    // En store agregué soporte para subir la imagen de la categoría a ImgBB y cambié los redirects por respuestas JSON.
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => ['required', 'string', 'max:255', 'unique:categorias,nombre'],
            'estado' => ['nullable'],
            'imagen' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,gif,bmp', 'max:10240'],
        ], [
            'nombre.required' => 'El nombre de la categoría es obligatorio.',
            'nombre.unique' => 'Ya existe otra categoría con este nombre.',
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

        $categoria = Categoria::create($datos);

        // return redirect()->route('categorias.index')->with('mensaje', 'Registro guardado exitosamente.');
        return response()->json([
            'success' => true,
            'message' => 'Categoría registrada exitosamente.',
            'data' => $categoria,
        ], 201);
    }

    // public function vistaEdicion($id = null)
    // {
    //     $categoria = Categoria::find($id);
    //     if (!$categoria) {
    //         return redirect()->route('categorias.listar');
    //     }
    //     return view('categorias.edicion', compact('categoria'));
    // }

    // Cambié vistaMostrar por show(Request $request, $id) y comenté la vista para retornar el detalle en JSON.
    public function show(Request $request, $id)
    {
        // 1. Validar que el ID exista en la base de datos
        $categoria = Categoria::find($id);

        if (!$categoria) {
            // return redirect()->route('categorias.listar')
            //     ->with('error', "La categoría con ID [{$id}] no existe en la base de datos.");
            return response()->json([
                'success' => false,
                'message' => "La categoría con ID [{$id}] no existe en la base de datos.",
            ], 404);
        }

        $registro = [
            'id' => $categoria->id,
            'nombre' => $categoria->nombre,
            'imagen' => $categoria->imagen,
            'estado' => $categoria->estado ? 'Activa' : 'Inactiva',
        ];

        // return view('categorias.mostrar', compact('categoria', 'registro'));
        return response()->json([
            'success' => true,
            'message' => 'Detalles de la categoría recuperados correctamente.',
            'data' => $registro,
        ], 200);
    }

    // Cambié actualizar por update(Request $request, $id) usando 'sometimes', quité 'descripcion' porque no existe en la tabla categorias y agregué subida de imagen.
    public function update(Request $request, $id)
    {
        // 1. Validar que el ID exista en la base de datos
        $categoria = Categoria::find($id);

        if (!$categoria) {
            // return redirect()->route('categorias.listar')
            //     ->with('error', "No se puede actualizar: la categoría con ID [{$id}] no existe en la base de datos.");
            return response()->json([
                'success' => false,
                'message' => "No se puede actualizar: la categoría con ID [{$id}] no existe en la base de datos.",
            ], 404);
        }

        // 2. Validación de los datos de entrada con Validator y regla 'sometimes'
        $validator = Validator::make($request->all(), [
            'nombre' => ['sometimes', 'required', 'string', 'max:255', 'unique:categorias,nombre,' . $id],
            'estado' => ['nullable'],
            'imagen' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,gif,bmp', 'max:10240'],
        ], [
            'nombre.required' => 'El nombre de la categoría es obligatorio.',
            'nombre.unique' => 'Ya existe otra categoría con este nombre.',
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
            $datos['estado'] = $this->parsearEstado($request->input('estado'), (bool) $categoria->estado);
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

        $categoria->update($datos);

        // return redirect()->route('categorias.listar')->with('success', 'Categoría actualizada exitosamente.');
        return response()->json([
            'success' => true,
            'message' => 'Categoría actualizada exitosamente.',
            'data' => $categoria,
        ], 200);
    }

    // Cambié borrar por destroy(Request $request, $id) conservando el borrado lógico y respondiendo en JSON.
    public function destroy(Request $request, $id)
    {
        // 1. Validar que el ID exista en la base de datos antes del proceso
        $categoria = Categoria::find($id);

        if (!$categoria) {
            // return redirect()->route('categorias.listar')
            //     ->with('error', "No se puede eliminar: la categoría con ID [{$id}] no existe en la base de datos.");
            return response()->json([
                'success' => false,
                'message' => "No se puede eliminar: la categoría con ID [{$id}] no existe en la base de datos.",
            ], 404);
        }

        // 2. Borrado lógico (el esquema tiene campo booleano 'estado' y restrictOnDelete desde productos)
        $categoria->estado = false;
        $categoria->save();

        // return redirect()->route('categorias.listar')->with('success', 'Categoría dada de baja (borrado lógico) exitosamente.');
        return response()->json([
            'success' => true,
            'message' => 'Categoría desactivada correctamente (borrado lógico).',
            'data' => $categoria,
        ], 200);
    }
}
