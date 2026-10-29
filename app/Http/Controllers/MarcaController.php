<?php

namespace App\Http\Controllers;

use App\Models\Marca;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MarcaController extends Controller
{
    private const MODELO = Marca::class;

    // Comenté los datos estáticos iniciales porque ahora consulto todo desde la tabla marcas en MySQL.
    // private array $datos = [
    //     ['id' => 1, 'nombre' => 'Ciel', 'origen' => 'México', 'productos' => 4, 'estado' => 'Activa'],
    //     ['id' => 2, 'nombre' => 'Bonafont', 'origen' => 'México', 'productos' => 5, 'estado' => 'Activa'],
    // ];

    // Cambié listar() por index(Request $request), incluí el campo imagen en el select y comenté la vista Blade para devolver JSON con success y data.
    public function index(Request $request)
    {
        $marcas = Marca::query()->get(['id', 'nombre', 'imagen', 'estado'])->map(fn (Marca $marca) => [
            'id' => $marca->id,
            'nombre' => $marca->nombre,
            'imagen' => $marca->imagen,
            'estado' => $marca->estado ? 'Activa' : 'Inactiva',
        ])->all();

        // return view('marcas.listado', compact('marcas'));
        return response()->json([
            'success' => true,
            'message' => 'Marcas recuperadas correctamente.',
            'data' => $marcas,
        ], 200);
    }

    // Comenté las funciones de vistas de formulario porque las altas ahora entran por POST a store() desde Postman.
    // public function vistaFormulario() { return $this->create(); }
    // public function create() { return view('marcas.create'); }
    // public function registrar(Request $request) { return $this->store($request); }

    // En store agregué soporte para imagen de la marca con ImgBB y cambié los redirects por respuestas JSON.
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => ['required', 'string', 'max:255', 'unique:marcas,nombre'],
            'estado' => ['nullable'],
            'imagen' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,gif,bmp', 'max:10240'],
        ], [
            'nombre.required' => 'El nombre de la marca es obligatorio.',
            'nombre.unique' => 'Ya existe otra marca con este nombre.',
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

        $marca = Marca::create($datos);

        // return redirect()->route('marcas.index')->with('mensaje', 'Registro guardado exitosamente.');
        return response()->json([
            'success' => true,
            'message' => 'Marca registrada exitosamente.',
            'data' => $marca,
        ], 201);
    }

    // public function vistaEdicion($id = null)
    // {
    //     $marca = Marca::find($id);
    //     if (!$marca) { return redirect()->route('marcas.listar'); }
    //     return view('marcas.edicion', compact('marca'));
    // }

    // Cambié vistaMostrar por show(Request $request, $id), agregué imagen en el arreglo de salida y retorno JSON.
    public function show(Request $request, $id)
    {
        // 1. Validar que el ID exista en la base de datos
        $marca = Marca::find($id);

        if (!$marca) {
            // return redirect()->route('marcas.listar')
            //     ->with('error', "La marca con ID [{$id}] no existe en la base de datos.");
            return response()->json([
                'success' => false,
                'message' => "La marca con ID [{$id}] no existe en la base de datos.",
            ], 404);
        }

        $registro = [
            'id' => $marca->id,
            'nombre' => $marca->nombre,
            'imagen' => $marca->imagen,
            'estado' => $marca->estado ? 'Activa' : 'Inactiva',
        ];

        // return view('marcas.mostrar', compact('marca', 'registro'));
        return response()->json([
            'success' => true,
            'message' => 'Detalles de la marca recuperados correctamente.',
            'data' => $registro,
        ], 200);
    }

    // Cambié actualizar por update(Request $request, $id) usando 'sometimes', carga opcional de imagen y respuesta JSON.
    public function update(Request $request, $id)
    {
        // 1. Validar que el ID exista en la base de datos
        $marca = Marca::find($id);

        if (!$marca) {
            // return redirect()->route('marcas.listar')
            //     ->with('error', "No se puede actualizar: la marca con ID [{$id}] no existe en la base de datos.");
            return response()->json([
                'success' => false,
                'message' => "No se puede actualizar: la marca con ID [{$id}] no existe en la base de datos.",
            ], 404);
        }

        // 2. Validación de los datos de entrada con Validator y regla 'sometimes'
        $validator = Validator::make($request->all(), [
            'nombre' => ['sometimes', 'required', 'string', 'max:255', 'unique:marcas,nombre,' . $id],
            'estado' => ['nullable'],
            'imagen' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,gif,bmp', 'max:10240'],
        ], [
            'nombre.required' => 'El nombre de la marca es obligatorio.',
            'nombre.unique' => 'Ya existe otra marca con este nombre.',
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
            $datos['estado'] = $this->parsearEstado($request->input('estado'), (bool) $marca->estado);
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

        $marca->update($datos);

        // return redirect()->route('marcas.listar')->with('success', 'Marca actualizada exitosamente.');
        return response()->json([
            'success' => true,
            'message' => 'Marca actualizada exitosamente.',
            'data' => $marca,
        ], 200);
    }

    // Cambié borrar por destroy(Request $request, $id) conservando el borrado lógico y retornando JSON.
    public function destroy(Request $request, $id)
    {
        // 1. Validar que el ID exista antes del proceso de borrado
        $marca = Marca::find($id);

        if (!$marca) {
            // return redirect()->route('marcas.listar')
            //     ->with('error', "No se puede eliminar: la marca con ID [{$id}] no existe en la base de datos.");
            return response()->json([
                'success' => false,
                'message' => "No se puede eliminar: la marca con ID [{$id}] no existe en la base de datos.",
            ], 404);
        }

        // 2. Borrado lógico: la tabla marcas tiene el campo booleano 'estado' y clave foránea restrictOnDelete en productos
        $marca->estado = false;
        $marca->save();

        // return redirect()->route('marcas.listar')->with('success', "La marca '{$marca->nombre}' fue dada de baja (Inactiva) correctamente.");
        return response()->json([
            'success' => true,
            'message' => "La marca '{$marca->nombre}' fue desactivada correctamente (borrado lógico).",
            'data' => $marca,
        ], 200);
    }
}
