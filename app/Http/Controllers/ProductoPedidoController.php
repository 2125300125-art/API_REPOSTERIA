<?php

namespace App\Http\Controllers;

use App\Models\ProductoPedido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductoPedidoController extends Controller
{
    private const MODELO = ProductoPedido::class;

    // Comenté los registros temporales de prueba porque ahora consumo directamente la tabla productos_pedido de MySQL.
    // private array $datos = [
    //     ['id' => 1, 'pedido_id' => 1, 'producto_id' => 1, 'cantidad' => 2, 'precio' => 48.00, 'descuento' => 0.00, 'estado' => 'Activo'],
    //     ['id' => 2, 'pedido_id' => 1, 'producto_id' => 2, 'cantidad' => 4, 'precio' => 16.50, 'descuento' => 0.00, 'estado' => 'Activo'],
    // ];

    // Cambié listar() por index(Request $request) trayendo las relaciones pedido y producto desde Eloquent y respondiendo en JSON con success y data.
    public function index(Request $request)
    {
        // $productos_pedido = $this->datosTemporales('productos_pedido', $this->datos);
        $productos_pedido = ProductoPedido::query()->with(['pedido.cliente', 'producto'])->get()->map(fn (ProductoPedido $detalle) => [
            'id' => $detalle->id,
            'pedido_id' => $detalle->pedido_id,
            'pedido' => '#' . $detalle->pedido_id . ($detalle->pedido && $detalle->pedido->cliente ? (' — ' . $detalle->pedido->cliente->nombres . ' ' . $detalle->pedido->cliente->apellidos) : ''),
            'producto_id' => $detalle->producto_id,
            'producto' => $detalle->producto->nombre ?? 'Sin producto',
            'cantidad' => $detalle->cantidad,
            'precio' => $detalle->precio,
            'descuento' => $detalle->descuento,
            'imagen' => $detalle->imagen ?: ($detalle->producto->imagen ?? null),
            'estado' => $detalle->estado ? 'Activo' : 'Inactivo',
        ])->all();

        // return view('productos_pedido.listado', compact('productos_pedido'));
        return response()->json([
            'success' => true,
            'message' => 'Productos por pedido recuperados correctamente.',
            'data' => $productos_pedido,
        ], 200);
    }

    // Comenté vistaFormulario y vistaEdicion porque en la API REST las altas y cambios entran directo a store() y update().
    // public function vistaFormulario() { return view('productos_pedido.formulario'); }

    // Cambié registrar() con sesión temporal por store(Request $request) guardando en MySQL con soporte de imagen y JSON 201.
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pedido_id' => ['required', 'exists:pedidos,id'],
            'producto_id' => ['required', 'exists:productos,id'],
            'cantidad' => ['required', 'integer', 'min:1'],
            'precio' => ['required', 'numeric', 'min:0'],
            'descuento' => ['nullable', 'numeric', 'min:0'],
            'estado' => ['nullable'],
            'imagen' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,gif,bmp', 'max:10240'],
        ], [
            'pedido_id.required' => 'Debe seleccionar un pedido.',
            'pedido_id.exists' => 'El pedido seleccionado no existe.',
            'producto_id.required' => 'Debe seleccionar un producto.',
            'producto_id.exists' => 'El producto seleccionado no existe.',
            'cantidad.required' => 'La cantidad es obligatoria.',
            'precio.required' => 'El precio es obligatorio.',
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

        $datos['descuento'] = $datos['descuento'] ?? 0;
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

        // $this->registrarDatoTemporal('productos_pedido', $this->datos, $datos);
        $detalle = ProductoPedido::create($datos);

        // return redirect()->route('productos_pedido.listar')->with('success', 'Detalle guardado exitosamente.');
        return response()->json([
            'success' => true,
            'message' => 'Producto por pedido registrado exitosamente.',
            'data' => $detalle,
        ], 201);
    }

    // public function vistaEdicion($id = 1)
    // {
    //     $registro = $this->buscarDatoTemporal('productos_pedido', $this->datos, $id);
    //     return view('productos_pedido.edicion', compact('registro'));
    // }

    // Cambié vistaMostrar por show(Request $request, $id) consultando el registro real en la base de datos y devolviendo JSON.
    public function show(Request $request, $id)
    {
        // $registro = $this->buscarDatoTemporal('productos_pedido', $this->datos, $id);
        $detalle = ProductoPedido::with(['pedido.cliente', 'producto'])->find($id);

        if (!$detalle) {
            return response()->json([
                'success' => false,
                'message' => "El registro de producto por pedido con ID [{$id}] no existe en la base de datos.",
            ], 404);
        }

        $registro = [
            'id' => $detalle->id,
            'pedido_id' => $detalle->pedido_id,
            'pedido' => '#' . $detalle->pedido_id . ($detalle->pedido && $detalle->pedido->cliente ? (' — ' . $detalle->pedido->cliente->nombres . ' ' . $detalle->pedido->cliente->apellidos) : ''),
            'producto_id' => $detalle->producto_id,
            'producto' => $detalle->producto->nombre ?? 'Sin producto',
            'cantidad' => $detalle->cantidad,
            'precio' => $detalle->precio,
            'descuento' => $detalle->descuento,
            'imagen' => $detalle->imagen ?: ($detalle->producto->imagen ?? null),
            'estado' => $detalle->estado ? 'Activo' : 'Inactivo',
        ];

        // return view('productos_pedido.mostrar', compact('registro'));
        return response()->json([
            'success' => true,
            'message' => 'Detalles de producto por pedido recuperados correctamente.',
            'data' => $registro,
        ], 200);
    }

    // Cambié actualizar por update(Request $request, $id) usando 'sometimes', imagen opcional y retorno JSON.
    public function update(Request $request, $id)
    {
        $detalle = ProductoPedido::find($id);

        if (!$detalle) {
            return response()->json([
                'success' => false,
                'message' => "No se puede actualizar: el registro con ID [{$id}] no existe en la base de datos.",
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'pedido_id' => ['sometimes', 'required', 'exists:pedidos,id'],
            'producto_id' => ['sometimes', 'required', 'exists:productos,id'],
            'cantidad' => ['sometimes', 'required', 'integer', 'min:1'],
            'precio' => ['sometimes', 'required', 'numeric', 'min:0'],
            'descuento' => ['nullable', 'numeric', 'min:0'],
            'estado' => ['nullable'],
            'imagen' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,gif,bmp', 'max:10240'],
        ], [
            'pedido_id.required' => 'Debe seleccionar un pedido.',
            'pedido_id.exists' => 'El pedido seleccionado no existe.',
            'producto_id.required' => 'Debe seleccionar un producto.',
            'producto_id.exists' => 'El producto seleccionado no existe.',
            'cantidad.required' => 'La cantidad es obligatoria.',
            'precio.required' => 'El precio es obligatorio.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Errores de validación.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $datos = $validator->validated();
        if (array_key_exists('descuento', $datos) && $datos['descuento'] === null) {
            $datos['descuento'] = $detalle->descuento ?? 0;
        }

        if ($request->has('estado')) {
            $datos['estado'] = $this->parsearEstado($request->input('estado'), (bool) $detalle->estado);
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

        // $this->actualizarDatoTemporal('productos_pedido', $this->datos, $id, $datos);
        $detalle->update($datos);

        // return redirect()->route('productos_pedido.listar')->with('success', 'Detalle actualizado exitosamente.');
        return response()->json([
            'success' => true,
            'message' => 'Producto por pedido actualizado exitosamente.',
            'data' => $detalle,
        ], 200);
    }

    // Cambié borrar por destroy(Request $request, $id) aplicando borrado lógico en la base de datos y devolviendo confirmación JSON.
    public function destroy(Request $request, $id)
    {
        $detalle = ProductoPedido::find($id);

        if (!$detalle) {
            return response()->json([
                'success' => false,
                'message' => "No se puede eliminar: el registro con ID [{$id}] no existe en la base de datos.",
            ], 404);
        }

        // $this->borrarDatoTemporal('productos_pedido', $this->datos, $id);
        $detalle->estado = false;
        $detalle->save();

        // return redirect()->route('productos_pedido.listar')->with('success', 'Detalle eliminado exitosamente.');
        return response()->json([
            'success' => true,
            'message' => 'Producto por pedido desactivado correctamente (borrado lógico).',
            'data' => $detalle,
        ], 200);
    }
}
