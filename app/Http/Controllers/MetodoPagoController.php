<?php

namespace App\Http\Controllers;

use App\Models\MetodoPago;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MetodoPagoController extends Controller
{
    private const MODELO = MetodoPago::class;

    // Comenté los datos de prueba en arreglo porque ahora consulto y modifico directamente la tabla metodos_pago en MySQL.
    // private array $datos = [
    //     ['id' => 1, 'nombre' => 'Efectivo', 'descripcion' => 'Pago al recibir el pedido', 'disponible' => 'En tienda y entrega'],
    //     ['id' => 2, 'nombre' => 'Transferencia', 'descripcion' => 'Transferencia bancaria', 'disponible' => 'Pago anticipado'],
    // ];

    // Cambié listar() por index(Request $request) comentando la vista Blade para devolver todos los métodos de pago en JSON con success y data.
    public function index(Request $request)
    {
        // $metodos_pago = $this->datosTemporales('metodos_pago', $this->datos);
        $metodos_pago = MetodoPago::query()->get()->map(fn (MetodoPago $metodo) => [
            'id' => $metodo->id,
            'nombre' => $metodo->nombre,
            'descripcion' => $metodo->descripcion,
            'imagen' => $metodo->imagen,
            'estado' => $metodo->estado ? 'Activo' : 'Inactivo',
        ])->all();

        // return view('metodos_pago.listado', compact('metodos_pago'));
        return response()->json([
            'success' => true,
            'message' => 'Métodos de pago recuperados correctamente.',
            'data' => $metodos_pago,
        ], 200);
    }

    // Comenté las funciones de formulario y edición web porque todo el flujo se prueba desde Postman en JSON.
    // public function vistaFormulario() { return view('metodos_pago.formulario'); }

    // Cambié registrar() por store(Request $request) usando el modelo MetodoPago, subida de imagen a ImgBB y respuesta 201.
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => ['required', 'string', 'max:255', 'unique:metodos_pago,nombre'],
            'descripcion' => ['nullable', 'string'],
            'estado' => ['nullable'],
            'imagen' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,gif,bmp', 'max:10240'],
        ], [
            'nombre.required' => 'El nombre del método de pago es obligatorio.',
            'nombre.unique' => 'Ya existe otro método de pago con este nombre.',
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

        // $this->registrarDatoTemporal('metodos_pago', $this->datos, $datos);
        $metodo = MetodoPago::create($datos);

        // return redirect()->route('metodos_pago.listar')->with('success', 'Método de pago guardado exitosamente.');
        return response()->json([
            'success' => true,
            'message' => 'Método de pago registrado exitosamente.',
            'data' => $metodo,
        ], 201);
    }

    // public function vistaEdicion($id = 1)
    // {
    //     $registro = $this->buscarDatoTemporal('metodos_pago', $this->datos, $id);
    //     return view('metodos_pago.edicion', compact('registro'));
    // }

    // Cambié vistaMostrar por show(Request $request, $id) consultando por Eloquent y devolviendo JSON o error 404.
    public function show(Request $request, $id)
    {
        // $registro = $this->buscarDatoTemporal('metodos_pago', $this->datos, $id);
        $metodo = MetodoPago::find($id);

        if (!$metodo) {
            return response()->json([
                'success' => false,
                'message' => "El método de pago con ID [{$id}] no existe en la base de datos.",
            ], 404);
        }

        $registro = [
            'id' => $metodo->id,
            'nombre' => $metodo->nombre,
            'descripcion' => $metodo->descripcion,
            'imagen' => $metodo->imagen,
            'estado' => $metodo->estado ? 'Activo' : 'Inactivo',
        ];

        // return view('metodos_pago.mostrar', compact('registro'));
        return response()->json([
            'success' => true,
            'message' => 'Detalles del método de pago recuperados correctamente.',
            'data' => $registro,
        ], 200);
    }

    // Cambié actualizar por update(Request $request, $id) usando 'sometimes', imagen opcional y retorno en JSON.
    public function update(Request $request, $id)
    {
        $metodo = MetodoPago::find($id);

        if (!$metodo) {
            return response()->json([
                'success' => false,
                'message' => "No se puede actualizar: el método de pago con ID [{$id}] no existe en la base de datos.",
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nombre' => ['sometimes', 'required', 'string', 'max:255', 'unique:metodos_pago,nombre,' . $id],
            'descripcion' => ['nullable', 'string'],
            'estado' => ['nullable'],
            'imagen' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,gif,bmp', 'max:10240'],
        ], [
            'nombre.required' => 'El nombre del método de pago es obligatorio.',
            'nombre.unique' => 'Ya existe otro método de pago con este nombre.',
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
            $datos['estado'] = $this->parsearEstado($request->input('estado'), (bool) $metodo->estado);
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

        // $this->actualizarDatoTemporal('metodos_pago', $this->datos, $id, $datos);
        $metodo->update($datos);

        // return redirect()->route('metodos_pago.listar')->with('success', 'Método de pago actualizado exitosamente.');
        return response()->json([
            'success' => true,
            'message' => 'Método de pago actualizado exitosamente.',
            'data' => $metodo,
        ], 200);
    }

    // Cambié borrar por destroy(Request $request, $id) usando borrado lógico con estado = false porque pedidos referencia metodos_pago.
    public function destroy(Request $request, $id)
    {
        $metodo = MetodoPago::find($id);

        if (!$metodo) {
            return response()->json([
                'success' => false,
                'message' => "No se puede eliminar: el método de pago con ID [{$id}] no existe en la base de datos.",
            ], 404);
        }

        // $this->borrarDatoTemporal('metodos_pago', $this->datos, $id);
        $metodo->estado = false;
        $metodo->save();

        // return redirect()->route('metodos_pago.listar')->with('success', 'Método de pago eliminado exitosamente.');
        return response()->json([
            'success' => true,
            'message' => 'Método de pago desactivado correctamente (borrado lógico).',
            'data' => $metodo,
        ], 200);
    }
}
