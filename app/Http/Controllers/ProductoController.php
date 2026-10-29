<?php

namespace App\Http\Controllers;

// use App\Models\Categoria;
// use App\Models\Marca;
// use App\Models\Presentacion;
use App\Models\Producto;
use App\Services\ImageUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductoController extends Controller
{
    private const MODELO = Producto::class;

    private ImageUploadService $imageService;

    public function __construct(ImageUploadService $imageService)
    {
        $this->imageService = $imageService;
    }

    // Comenté este arreglo temporal que tenía en las primeras prácticas porque ahora consulto todo desde la tabla productos en MySQL.
    // private array $datos = [
    //     ['id' => 1, 'nombre' => 'Garrafón de agua 20 L', 'marca' => 'Ciel', 'presentacion' => 'Garrafón retornable', 'precio' => 48.00, 'existencia' => 36, 'estado' => 'Activo'],
    //     ['id' => 2, 'nombre' => 'Agua purificada 1.5 L', 'marca' => 'Bonafont', 'presentacion' => 'Botella individual', 'precio' => 16.50, 'existencia' => 84, 'estado' => 'Activo'],
    // ];

    // Configuré index recibiendo Request para reemplazar mi función listar() de vistas y devolver los productos en JSON con la estructura del controlador base (success, message, data).
    public function index(Request $request)
    {
        $productos = Producto::query()->with(['categoria', 'marca', 'presentacion'])->get()->map(fn (Producto $producto) => [
            'id' => $producto->id,
            'nombre' => $producto->nombre,
            'descripcion' => $producto->descripcion,
            'categoria_id' => $producto->categoria_id,
            'categoria' => $producto->categoria->nombre ?? 'N/A',
            'marca_id' => $producto->marca_id,
            'marca' => $producto->marca->nombre ?? 'N/A',
            'presentacion_id' => $producto->presentacion_id,
            'presentacion' => $producto->presentacion->nombre ?? 'N/A',
            'precio' => $producto->precio,
            'existencia' => $producto->existencia,
            'descuento' => $producto->descuento,
            'imagen' => $producto->imagen,
            'imagen_secundaria' => $producto->imagen_secundaria,
            'imagen_terciaria' => $producto->imagen_terciaria,
            'estado' => $producto->estado ? 'Activo' : 'Inactivo',
        ])->all();

        // Comenté el retorno a la vista Blade listado y ahora devuelvo los datos en JSON.
        // return view('productos.listado', compact('productos'));
        return response()->json([
            'success' => true,
            'message' => 'Productos recuperados correctamente.',
            'data' => $productos,
        ], 200);
    }

    // Comenté las funciones vistaFormulario, create y registrar porque en la API REST ya no renderizo formularios HTML,
    // sino que envío los datos directamente por POST desde Postman hacia store().
    // public function vistaFormulario()
    // {
    //     return $this->create();
    // }

    // public function create()
    // {
    //     $categorias = Categoria::all();
    //     $marcas = Marca::all();
    //     $presentaciones = Presentacion::all();
    //     return view('productos.create', compact('categorias', 'marcas', 'presentaciones'));
    // }

    // public function registrar(Request $request)
    // {
    //     return $this->store($request);
    // }

    // Adapté store validando con Validator, usando DB::transaction, subiendo hasta 3 imágenes a ImgBB y respondiendo con JSON 201.
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'categoria_id' => ['required', 'exists:categorias,id'],
            'presentacion_id' => ['required', 'exists:presentaciones,id'],
            'marca_id' => ['required', 'exists:marcas,id'],
            'precio' => ['required', 'numeric', 'min:0'],
            'existencia' => ['required', 'integer', 'min:0'],
            'descuento' => ['nullable', 'numeric', 'min:0'],
            'estado' => ['nullable'],
            'imagen' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,gif,bmp', 'max:10240'],
            'imagen_secundaria' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,gif,bmp', 'max:10240'],
            'imagen_terciaria' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,gif,bmp', 'max:10240'],
        ]);

        if ($validator->fails()) {
            // Comenté el redirect de errores web para retornar código 422 en formato JSON en Postman.
            // return redirect()->back()->withErrors($validator)->withInput();
            return response()->json([
                'success' => false,
                'message' => 'Errores de validación.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $producto = DB::transaction(function () use ($request, $validator) {
                $datos = $validator->validated();
                unset($datos['imagen'], $datos['imagen_secundaria'], $datos['imagen_terciaria']);

                $datos['descuento'] = $datos['descuento'] ?? 0;
                $datos['estado'] = $request->has('estado')
                    ? $this->parsearEstado($request->input('estado'), true)
                    : true;

                // 1. Enviar las imágenes seleccionadas a la API externa de almacenamiento (ImgBB)
                foreach (['imagen', 'imagen_secundaria', 'imagen_terciaria'] as $campoImg) {
                    if ($request->hasFile($campoImg)) {
                        $datos[$campoImg] = $this->imageService->subirImagen($request->file($campoImg));
                    }
                }

                // 2. Guardar el registro en la base de datos con las URLs públicas devueltas por la API
                return Producto::create($datos);
            });

            // Comenté la redirección a productos.index y retorno el producto recién creado en JSON con estatus 201.
            // return redirect()->route('productos.index')->with('mensaje', 'Producto registrado exitosamente con imagen almacenada en la API externa.');
            return response()->json([
                'success' => true,
                'message' => 'Producto registrado exitosamente con imagen almacenada en la API externa.',
                'data' => $producto,
            ], 201);
        } catch (\Exception $e) {
            // return redirect()->back()->withErrors(['imagen' => $e->getMessage()])->withInput();
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar el producto: ' . $e->getMessage(),
            ], 500);
        }
    }

    // Comenté vistaEdicion porque en la API REST consulto un producto individual con show() y lo actualizo directo en update().
    // public function vistaEdicion($id = null)
    // {
    //     $producto = Producto::find($id);
    //     if (!$producto) {
    //         return redirect()->route('productos.listar')
    //             ->with('error', "El producto con ID [{$id}] no existe en la base de datos.");
    //     }
    //     $categorias = Categoria::all();
    //     $marcas = Marca::all();
    //     $presentaciones = Presentacion::all();
    //     return view('productos.edicion', compact('producto', 'categorias', 'marcas', 'presentaciones'));
    // }

    // Cambié vistaMostrar por show recibiendo Request e id, y corregí la validación de no encontrado para devolver JSON 404 en lugar de redirect.
    public function show(Request $request, $id)
    {
        // 1. Validar que el ID exista en la base de datos
        $producto = Producto::with(['categoria', 'marca', 'presentacion'])->find($id);

        if (!$producto) {
            // return redirect()->route('productos.listar')
            //     ->with('error', "El producto con ID [{$id}] no existe en la base de datos.")
            //     ->withErrors(['error' => "El producto con ID [{$id}] no existe en la base de datos."]);
            return response()->json([
                'success' => false,
                'message' => "El producto con ID [{$id}] no existe en la base de datos.",
            ], 404);
        }

        // 2. Mostrar nombres de tablas relacionadas junto con sus datos e imágenes
        $registro = [
            'id' => $producto->id,
            'nombre' => $producto->nombre,
            'descripcion' => $producto->descripcion,
            'categoria_id' => $producto->categoria_id,
            'categoria' => $producto->categoria->nombre ?? 'Sin categoría',
            'marca_id' => $producto->marca_id,
            'marca' => $producto->marca->nombre ?? 'Sin marca',
            'presentacion_id' => $producto->presentacion_id,
            'presentacion' => $producto->presentacion->nombre ?? 'Sin presentación',
            'precio' => $producto->precio,
            'existencia' => $producto->existencia,
            'descuento' => $producto->descuento,
            'imagen' => $producto->imagen,
            'imagen_secundaria' => $producto->imagen_secundaria,
            'imagen_terciaria' => $producto->imagen_terciaria,
            'estado' => $producto->estado ? 'Activo' : 'Inactivo',
        ];

        // return view('productos.mostrar', compact('producto', 'registro'));
        return response()->json([
            'success' => true,
            'message' => 'Detalles del producto recuperados correctamente.',
            'data' => $registro,
        ], 200);
    }

    // Cambié mi función actualizar por update usando 'sometimes' para permitir actualizaciones completas o parciales por PUT en Postman.
    public function update(Request $request, $id)
    {
        // 1. Validar que el ID exista en la base de datos
        $producto = Producto::find($id);

        if (!$producto) {
            // return redirect()->route('productos.listar')
            //     ->with('error', "No se puede actualizar: el producto con ID [{$id}] no existe en la base de datos.")
            //     ->withErrors(['error' => "No se puede actualizar: el producto con ID [{$id}] no existe en la base de datos."]);
            return response()->json([
                'success' => false,
                'message' => "No se puede actualizar: el producto con ID [{$id}] no existe en la base de datos.",
            ], 404);
        }

        // 2. Validación de datos de entrada con Validator y regla 'sometimes'
        $validator = Validator::make($request->all(), [
            'nombre' => ['sometimes', 'required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'categoria_id' => ['sometimes', 'required', 'exists:categorias,id'],
            'presentacion_id' => ['sometimes', 'required', 'exists:presentaciones,id'],
            'marca_id' => ['sometimes', 'required', 'exists:marcas,id'],
            'precio' => ['sometimes', 'required', 'numeric', 'min:0'],
            'existencia' => ['sometimes', 'required', 'integer', 'min:0'],
            'descuento' => ['nullable', 'numeric', 'min:0'],
            'estado' => ['nullable'],
            'imagen' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,gif,bmp', 'max:10240'],
            'imagen_secundaria' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,gif,bmp', 'max:10240'],
            'imagen_terciaria' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,gif,bmp', 'max:10240'],
        ], [
            'nombre.required' => 'El nombre del producto es obligatorio.',
            'categoria_id.required' => 'Debe seleccionar una categoría.',
            'categoria_id.exists' => 'La categoría seleccionada no existe.',
            'marca_id.required' => 'Debe seleccionar una marca.',
            'marca_id.exists' => 'La marca seleccionada no existe.',
            'presentacion_id.required' => 'Debe seleccionar una presentación.',
            'presentacion_id.exists' => 'La presentación seleccionada no existe.',
            'precio.required' => 'El precio es obligatorio.',
            'existencia.required' => 'La existencia es obligatoria.',
        ]);

        if ($validator->fails()) {
            // return redirect()->back()->withErrors($validator)->withInput();
            return response()->json([
                'success' => false,
                'message' => 'Errores de validación.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $datos = $validator->validated();
            unset($datos['imagen'], $datos['imagen_secundaria'], $datos['imagen_terciaria']);

            if (array_key_exists('descuento', $datos) && $datos['descuento'] === null) {
                $datos['descuento'] = $producto->descuento ?? 0;
            }

            if ($request->has('estado')) {
                $datos['estado'] = $this->parsearEstado($request->input('estado'), (bool) $producto->estado);
            } else {
                unset($datos['estado']);
            }

            // 3. Subir nueva imagen a la API externa si seleccioné un archivo nuevo en Postman
            foreach (['imagen', 'imagen_secundaria', 'imagen_terciaria'] as $campoImg) {
                if ($request->hasFile($campoImg)) {
                    $datos[$campoImg] = $this->imageService->subirImagen($request->file($campoImg));
                }
            }

            // 4. Actualizar el registro en la base de datos
            $producto->update($datos);

            // return redirect()->route('productos.listar')->with('success', 'Producto actualizado exitosamente.');
            return response()->json([
                'success' => true,
                'message' => 'Producto actualizado exitosamente.',
                'data' => $producto,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el producto: ' . $e->getMessage(),
            ], 500);
        }
    }

    // Cambié mi función borrar por destroy recibiendo Request para realizar el borrado lógico por DELETE desde Postman y responder en JSON.
    public function destroy(Request $request, $id)
    {
        // 1. Validar que el ID exista en la base de datos antes del proceso
        $producto = Producto::find($id);

        if (!$producto) {
            // return redirect()->route('productos.listar')
            //     ->with('error', "No se puede eliminar: el producto con ID [{$id}] no existe en la base de datos.")
            //     ->withErrors(['error' => "No se puede eliminar: el producto con ID [{$id}] no existe en la base de datos."]);
            return response()->json([
                'success' => false,
                'message' => "No se puede eliminar: el producto con ID [{$id}] no existe en la base de datos.",
            ], 404);
        }

        // 2. Borrado lógico conservando las imágenes en la nube y sin romper relaciones foráneas
        $producto->estado = false;
        $producto->save();

        // Alternativa borrado físico: $producto->delete();

        // return redirect()->route('productos.listar')->with('success', 'Producto dado de baja (borrado lógico) exitosamente.');
        return response()->json([
            'success' => true,
            'message' => 'Producto desactivado correctamente (borrado lógico).',
            'data' => $producto,
        ], 200);
    }
}
