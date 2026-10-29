<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PedidoController extends Controller
{
    private const MODELO = Pedido::class;

    // Cambié listar() por index(Request $request) comentando el retorno a la vista Blade para devolver los pedidos en JSON con success y data.
    public function index(Request $request)
    {
        $pedidos = Pedido::query()->with(['cliente', 'metodoPago'])->get()->map(fn (Pedido $pedido) => [
            'id' => $pedido->id,
            'cliente_id' => $pedido->cliente_id,
            'cliente' => $pedido->cliente ? ($pedido->cliente->nombres . ' ' . $pedido->cliente->apellidos) : 'Cliente',
            'metodo_pago_id' => $pedido->metodo_pago_id,
            'metodo_pago' => $pedido->metodoPago ? $pedido->metodoPago->nombre : 'Efectivo',
            'fecha' => $pedido->fecha,
            'iva' => $pedido->iva,
            'descuento' => $pedido->descuento,
            'total' => $pedido->total,
            'imagen' => $pedido->imagen,
            'estado' => $pedido->estado ? 'Activo' : 'Inactivo',
        ])->all();

        // return view('pedidos.listado', compact('pedidos'));
        return response()->json([
            'success' => true,
            'message' => 'Pedidos recuperados correctamente.',
            'data' => $pedidos,
        ], 200);
    }

    // Comenté vistaFormulario y vistaEdicion porque en la API ya no renderizo vistas de formulario para pedidos.
    // public function vistaFormulario()
    // {
    //     return view('pedidos.formulario');
    // }

    // Cambié registrar() por store(Request $request) validando cliente, método de pago, cálculo de IVA, imagen opcional y retorno JSON 201.
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cliente_id' => ['required', 'exists:clientes,id'],
            'metodo_pago_id' => ['required', 'exists:metodos_pago,id'],
            'fecha' => ['required', 'date'],
            'total' => ['required', 'numeric', 'min:0'],
            'iva' => ['nullable', 'numeric', 'min:0'],
            'descuento' => ['nullable', 'numeric', 'min:0'],
            'estado' => ['nullable'],
            'imagen' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,gif,bmp', 'max:10240'],
        ], [
            'cliente_id.required' => 'Debe seleccionar un cliente.',
            'cliente_id.exists' => 'El cliente seleccionado no existe.',
            'metodo_pago_id.required' => 'Debe seleccionar un método de pago.',
            'metodo_pago_id.exists' => 'El método de pago seleccionado no existe.',
            'fecha.required' => 'La fecha del pedido es obligatoria.',
            'total.required' => 'El total del pedido es obligatorio.',
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

        $total = (float) $datos['total'];
        $datos['iva'] = isset($datos['iva']) ? (float) $datos['iva'] : round($total * 0.16, 2);
        $datos['descuento'] = isset($datos['descuento']) ? (float) $datos['descuento'] : 0.00;
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

        $pedido = Pedido::create($datos);

        // return redirect()->route('pedidos.listar')->with('success', 'Pedido registrado exitosamente.');
        return response()->json([
            'success' => true,
            'message' => 'Pedido registrado exitosamente.',
            'data' => $pedido,
        ], 201);
    }

    // public function vistaEdicion($id = 1)
    // {
    //     $pedido = Pedido::find($id) ?? Pedido::first();
    //     return view('pedidos.edicion', ['registro' => $pedido]);
    // }

    // Cambié vistaMostrar por show(Request $request, $id) quitando el fallback Pedido::first() para retornar 404 real en JSON cuando no exista.
    public function show(Request $request, $id)
    {
        // $pedido = Pedido::with(['cliente', 'metodoPago'])->find($id) ?? Pedido::with(['cliente', 'metodoPago'])->first();
        $pedido = Pedido::with(['cliente', 'metodoPago'])->find($id);

        if (!$pedido) {
            return response()->json([
                'success' => false,
                'message' => "El pedido con ID [{$id}] no existe en la base de datos.",
            ], 404);
        }

        $registro = [
            'id' => $pedido->id,
            'cliente_id' => $pedido->cliente_id,
            'cliente' => $pedido->cliente ? ($pedido->cliente->nombres . ' ' . $pedido->cliente->apellidos) : 'Sin cliente',
            'metodo_pago_id' => $pedido->metodo_pago_id,
            'metodo_pago' => $pedido->metodoPago ? $pedido->metodoPago->nombre : 'Sin método de pago',
            'fecha' => $pedido->fecha,
            'iva' => $pedido->iva,
            'descuento' => $pedido->descuento,
            'total' => $pedido->total,
            'imagen' => $pedido->imagen,
            'estado' => $pedido->estado ? 'Activo' : 'Inactivo',
        ];

        // return view('pedidos.mostrar', ['registro' => $registro]);
        return response()->json([
            'success' => true,
            'message' => 'Detalles del pedido recuperados correctamente.',
            'data' => $registro,
        ], 200);
    }

    // Cambié actualizar por update(Request $request, $id) usando 'sometimes', imagen opcional y respuesta JSON.
    public function update(Request $request, $id)
    {
        $pedido = Pedido::find($id);

        if (!$pedido) {
            return response()->json([
                'success' => false,
                'message' => "No se puede actualizar: el pedido con ID [{$id}] no existe en la base de datos.",
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'cliente_id' => ['sometimes', 'required', 'exists:clientes,id'],
            'metodo_pago_id' => ['sometimes', 'required', 'exists:metodos_pago,id'],
            'fecha' => ['sometimes', 'required', 'date'],
            'total' => ['sometimes', 'required', 'numeric', 'min:0'],
            'iva' => ['nullable', 'numeric', 'min:0'],
            'descuento' => ['nullable', 'numeric', 'min:0'],
            'estado' => ['nullable'],
            'imagen' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,gif,bmp', 'max:10240'],
        ], [
            'cliente_id.required' => 'Debe seleccionar un cliente.',
            'cliente_id.exists' => 'El cliente seleccionado no existe.',
            'metodo_pago_id.required' => 'Debe seleccionar un método de pago.',
            'metodo_pago_id.exists' => 'El método de pago seleccionado no existe.',
            'fecha.required' => 'La fecha del pedido es obligatoria.',
            'total.required' => 'El total del pedido es obligatorio.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Errores de validación.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $datos = $validator->validated();
        if (isset($datos['total']) && (!array_key_exists('iva', $datos) || $datos['iva'] === null)) {
            $datos['iva'] = round(((float) $datos['total']) * 0.16, 2);
        } elseif (array_key_exists('iva', $datos) && $datos['iva'] === null) {
            $datos['iva'] = (float) ($pedido->iva ?? 0);
        }

        if (array_key_exists('descuento', $datos) && $datos['descuento'] === null) {
            $datos['descuento'] = (float) ($pedido->descuento ?? 0);
        }

        if ($request->has('estado')) {
            $datos['estado'] = $this->parsearEstado($request->input('estado'), (bool) $pedido->estado);
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

        $pedido->update($datos);

        // return redirect()->route('pedidos.listar')->with('success', 'Pedido actualizado exitosamente.');
        return response()->json([
            'success' => true,
            'message' => 'Pedido actualizado exitosamente.',
            'data' => $pedido,
        ], 200);
    }

    // Cambié borrar por destroy(Request $request, $id) y comenté $pedido->delete() para hacer borrado lógico y no romper productos_pedido ni entregas.
    public function destroy(Request $request, $id)
    {
        $pedido = Pedido::find($id);

        if (!$pedido) {
            return response()->json([
                'success' => false,
                'message' => "No se puede eliminar: el pedido con ID [{$id}] no existe en la base de datos.",
            ], 404);
        }

        // $pedido->delete();
        $pedido->estado = false;
        $pedido->save();

        // return redirect()->route('pedidos.listar')->with('success', 'Pedido eliminado exitosamente.');
        return response()->json([
            'success' => true,
            'message' => 'Pedido desactivado correctamente (borrado lógico).',
            'data' => $pedido,
        ], 200);
    }
}
