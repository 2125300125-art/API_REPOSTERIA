<?php

namespace App\Http\Controllers;

use App\Models\Entrega;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EntregaController extends Controller
{
    private const MODELO = Entrega::class;

    // Comenté los datos temporales de entregas que usaba antes porque ahora consulto y guardo en la tabla entregas de MySQL.
    // private array $datos = [
    //     ['id' => 1, 'pedido_id' => 1, 'cliente' => 'Ana Sofía Ramírez', 'repartidor' => 'Carlos Mendoza', 'direccion' => 'Av. Reforma 120, Centro', 'fecha' => '2026-09-25', 'hora' => '10:30', 'estado' => 'Programada'],
    //     ['id' => 2, 'pedido_id' => 2, 'cliente' => 'Luis Hernández', 'repartidor' => 'Miguel Torres', 'direccion' => 'Calle Lago 45, Jardines', 'fecha' => '2026-09-25', 'hora' => '12:00', 'estado' => 'Entregada'],
    // ];

    // Cambié listar() por index(Request $request) trayendo pedido, repartidor y dirección por Eloquent y respondiendo en JSON con success y data.
    public function index(Request $request)
    {
        // $entregas = $this->datosTemporales('entregas', $this->datos);
        $entregas = Entrega::query()->with(['pedido.cliente', 'repartidor', 'direccion'])->get()->map(fn (Entrega $entrega) => [
            'id' => $entrega->id,
            'pedido_id' => $entrega->pedido_id,
            'pedido' => '#' . $entrega->pedido_id . ($entrega->pedido && $entrega->pedido->cliente ? (' — ' . $entrega->pedido->cliente->nombres . ' ' . $entrega->pedido->cliente->apellidos) : ''),
            'repartidor_id' => $entrega->repartidor_id,
            'repartidor' => $entrega->repartidor ? ($entrega->repartidor->nombres . ' ' . $entrega->repartidor->apellidos) : 'Sin repartidor',
            'direccion_id' => $entrega->direccion_id,
            'direccion' => $entrega->direccion ? ($entrega->direccion->calle . ' ' . $entrega->direccion->numero . ', ' . $entrega->direccion->colonia . ', ' . $entrega->direccion->ciudad) : 'Sin dirección',
            'fecha' => $entrega->fecha,
            'hora' => $entrega->hora,
            'imagen' => $entrega->imagen,
            'estado' => $entrega->estado ? 'Activa' : 'Inactiva',
        ])->all();

        // return view('entregas.listado', compact('entregas'));
        return response()->json([
            'success' => true,
            'message' => 'Entregas recuperadas correctamente.',
            'data' => $entregas,
        ], 200);
    }

    // Comenté las funciones de vistas Blade porque el alta y edición de entregas ahora se hace vía Postman.
    // public function vistaFormulario() { return view('entregas.formulario'); }

    // Cambié registrar() con sesión temporal por store(Request $request) validando llaves foráneas, subiendo evidencia fotográfica a ImgBB y retornando JSON 201.
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pedido_id' => ['required', 'exists:pedidos,id'],
            'repartidor_id' => ['required', 'exists:repartidores,id'],
            'direccion_id' => ['required', 'exists:direcciones,id'],
            'fecha' => ['required', 'date'],
            'hora' => ['nullable', 'string', 'max:20'],
            'estado' => ['nullable'],
            'imagen' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,gif,bmp', 'max:10240'],
        ], [
            'pedido_id.required' => 'Debe seleccionar un pedido.',
            'pedido_id.exists' => 'El pedido seleccionado no existe.',
            'repartidor_id.required' => 'Debe seleccionar un repartidor.',
            'repartidor_id.exists' => 'El repartidor seleccionado no existe.',
            'direccion_id.required' => 'Debe seleccionar una dirección.',
            'direccion_id.exists' => 'La dirección seleccionada no existe.',
            'fecha.required' => 'La fecha de entrega es obligatoria.',
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

        // $this->registrarDatoTemporal('entregas', $this->datos, $datos);
        $entrega = Entrega::create($datos);

        // return redirect()->route('entregas.listar')->with('success', 'Entrega registrada exitosamente.');
        return response()->json([
            'success' => true,
            'message' => 'Entrega registrada exitosamente.',
            'data' => $entrega,
        ], 201);
    }

    // public function vistaEdicion($id = 1)
    // {
    //     $registro = $this->buscarDatoTemporal('entregas', $this->datos, $id);
    //     return view('entregas.edicion', compact('registro'));
    // }

    // Cambié vistaMostrar por show(Request $request, $id) consultando en MySQL con sus relaciones y devolviendo JSON o 404.
    public function show(Request $request, $id)
    {
        // $registro = $this->buscarDatoTemporal('entregas', $this->datos, $id);
        $entrega = Entrega::with(['pedido.cliente', 'repartidor', 'direccion'])->find($id);

        if (!$entrega) {
            return response()->json([
                'success' => false,
                'message' => "La entrega con ID [{$id}] no existe en la base de datos.",
            ], 404);
        }

        $registro = [
            'id' => $entrega->id,
            'pedido_id' => $entrega->pedido_id,
            'pedido' => '#' . $entrega->pedido_id . ($entrega->pedido && $entrega->pedido->cliente ? (' — ' . $entrega->pedido->cliente->nombres . ' ' . $entrega->pedido->cliente->apellidos) : ''),
            'repartidor_id' => $entrega->repartidor_id,
            'repartidor' => $entrega->repartidor ? ($entrega->repartidor->nombres . ' ' . $entrega->repartidor->apellidos) : 'Sin repartidor',
            'direccion_id' => $entrega->direccion_id,
            'direccion' => $entrega->direccion ? ($entrega->direccion->calle . ' ' . $entrega->direccion->numero . ', ' . $entrega->direccion->colonia . ', ' . $entrega->direccion->ciudad) : 'Sin dirección',
            'fecha' => $entrega->fecha,
            'hora' => $entrega->hora,
            'imagen' => $entrega->imagen,
            'estado' => $entrega->estado ? 'Activa' : 'Inactiva',
        ];

        // return view('entregas.mostrar', compact('registro'));
        return response()->json([
            'success' => true,
            'message' => 'Detalles de la entrega recuperados correctamente.',
            'data' => $registro,
        ], 200);
    }

    // Cambié actualizar por update(Request $request, $id) usando 'sometimes', imagen opcional y respondiendo en JSON.
    public function update(Request $request, $id)
    {
        $entrega = Entrega::find($id);

        if (!$entrega) {
            return response()->json([
                'success' => false,
                'message' => "No se puede actualizar: la entrega con ID [{$id}] no existe en la base de datos.",
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'pedido_id' => ['sometimes', 'required', 'exists:pedidos,id'],
            'repartidor_id' => ['sometimes', 'required', 'exists:repartidores,id'],
            'direccion_id' => ['sometimes', 'required', 'exists:direcciones,id'],
            'fecha' => ['sometimes', 'required', 'date'],
            'hora' => ['nullable', 'string', 'max:20'],
            'estado' => ['nullable'],
            'imagen' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,gif,bmp', 'max:10240'],
        ], [
            'pedido_id.required' => 'Debe seleccionar un pedido.',
            'pedido_id.exists' => 'El pedido seleccionado no existe.',
            'repartidor_id.required' => 'Debe seleccionar un repartidor.',
            'repartidor_id.exists' => 'El repartidor seleccionado no existe.',
            'direccion_id.required' => 'Debe seleccionar una dirección.',
            'direccion_id.exists' => 'La dirección seleccionada no existe.',
            'fecha.required' => 'La fecha de entrega es obligatoria.',
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
            $datos['estado'] = $this->parsearEstado($request->input('estado'), (bool) $entrega->estado);
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

        // $this->actualizarDatoTemporal('entregas', $this->datos, $id, $datos);
        $entrega->update($datos);

        // return redirect()->route('entregas.listar')->with('success', 'Entrega actualizada exitosamente.');
        return response()->json([
            'success' => true,
            'message' => 'Entrega actualizada exitosamente.',
            'data' => $entrega,
        ], 200);
    }

    // Cambié borrar por destroy(Request $request, $id) aplicando borrado lógico en la base de datos y respondiendo en JSON.
    public function destroy(Request $request, $id)
    {
        $entrega = Entrega::find($id);

        if (!$entrega) {
            return response()->json([
                'success' => false,
                'message' => "No se puede eliminar: la entrega con ID [{$id}] no existe en la base de datos.",
            ], 404);
        }

        // $this->borrarDatoTemporal('entregas', $this->datos, $id);
        $entrega->estado = false;
        $entrega->save();

        // return redirect()->route('entregas.listar')->with('success', 'Entrega eliminada exitosamente.');
        return response()->json([
            'success' => true,
            'message' => 'Entrega desactivada correctamente (borrado lógico).',
            'data' => $entrega,
        ], 200);
    }
}
