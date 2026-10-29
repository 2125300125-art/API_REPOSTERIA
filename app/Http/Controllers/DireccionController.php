<?php

namespace App\Http\Controllers;

use App\Models\Direccion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DireccionController extends Controller
{
    private const MODELO = Direccion::class;

    // Comenté este arreglo temporal de direcciones porque ahora leo y guardo todo directamente en la tabla direcciones de MySQL.
    // private array $datos = [
    //     ['id' => 1, 'cliente' => 'Ana Sofía Ramírez', 'calle' => 'Avenida Reforma', 'numero' => '120', 'colonia' => 'Centro', 'ciudad' => 'Puebla', 'codigo_postal' => '72000'],
    //     ['id' => 2, 'cliente' => 'Luis Hernández', 'calle' => 'Calle Lago', 'numero' => '45', 'colonia' => 'Jardines', 'ciudad' => 'Puebla', 'codigo_postal' => '72100'],
    // ];

    // Cambié listar() por index(Request $request) consultando las direcciones con su cliente en Eloquent y respondiendo en JSON con success y data.
    public function index(Request $request)
    {
        // $direcciones = $this->datosTemporales('direcciones', $this->datos);
        $direcciones = Direccion::query()->with('cliente')->get()->map(fn (Direccion $direccion) => [
            'id' => $direccion->id,
            'cliente_id' => $direccion->cliente_id,
            'cliente' => $direccion->cliente ? ($direccion->cliente->nombres . ' ' . $direccion->cliente->apellidos) : 'Sin cliente',
            'calle' => $direccion->calle,
            'numero' => $direccion->numero,
            'colonia' => $direccion->colonia,
            'ciudad' => $direccion->ciudad,
            'codigo_postal' => $direccion->codigo_postal,
            'referencia' => $direccion->referencia,
            'imagen' => $direccion->imagen,
            'estado' => $direccion->estado ? 'Activa' : 'Inactiva',
        ])->all();

        // return view('direcciones.listado', compact('direcciones'));
        return response()->json([
            'success' => true,
            'message' => 'Direcciones recuperadas correctamente.',
            'data' => $direcciones,
        ], 200);
    }

    // Comenté vistaFormulario y vistaEdicion porque en la API REST ya no muestro vistas Blade.
    // public function vistaFormulario() { return view('direcciones.formulario'); }

    // Cambié registrar() con sesión temporal por store(Request $request) guardando en la base de datos con soporte de imagen y JSON 201.
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cliente_id' => ['required', 'exists:clientes,id'],
            'calle' => ['required', 'string', 'max:255'],
            'numero' => ['required', 'string', 'max:50'],
            'colonia' => ['required', 'string', 'max:255'],
            'ciudad' => ['required', 'string', 'max:255'],
            'codigo_postal' => ['required', 'string', 'size:5'],
            'referencia' => ['nullable', 'string', 'max:255'],
            'estado' => ['nullable'],
            'imagen' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,gif,bmp', 'max:10240'],
        ], [
            'cliente_id.required' => 'Debe seleccionar un cliente.',
            'cliente_id.exists' => 'El cliente seleccionado no existe.',
            'calle.required' => 'La calle es obligatoria.',
            'numero.required' => 'El número es obligatorio.',
            'colonia.required' => 'La colonia es obligatoria.',
            'ciudad.required' => 'La ciudad es obligatoria.',
            'codigo_postal.size' => 'El código postal debe tener 5 caracteres.',
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

        // $this->registrarDatoTemporal('direcciones', $this->datos, $datos);
        $direccion = Direccion::create($datos);

        // return redirect()->route('direcciones.listar')->with('success', 'Dirección registrada exitosamente.');
        return response()->json([
            'success' => true,
            'message' => 'Dirección registrada exitosamente.',
            'data' => $direccion,
        ], 201);
    }

    // public function vistaEdicion($id = 1)
    // {
    //     $registro = $this->buscarDatoTemporal('direcciones', $this->datos, $id);
    //     return view('direcciones.edicion', compact('registro'));
    // }

    // Cambié vistaMostrar por show(Request $request, $id) consultando en MySQL y devolviendo 404 en JSON si el ID no existe.
    public function show(Request $request, $id)
    {
        // $registro = $this->buscarDatoTemporal('direcciones', $this->datos, $id);
        $direccion = Direccion::with('cliente')->find($id);

        if (!$direccion) {
            return response()->json([
                'success' => false,
                'message' => "La dirección con ID [{$id}] no existe en la base de datos.",
            ], 404);
        }

        $registro = [
            'id' => $direccion->id,
            'cliente_id' => $direccion->cliente_id,
            'cliente' => $direccion->cliente ? ($direccion->cliente->nombres . ' ' . $direccion->cliente->apellidos) : 'Sin cliente',
            'calle' => $direccion->calle,
            'numero' => $direccion->numero,
            'colonia' => $direccion->colonia,
            'ciudad' => $direccion->ciudad,
            'codigo_postal' => $direccion->codigo_postal,
            'referencia' => $direccion->referencia,
            'imagen' => $direccion->imagen,
            'estado' => $direccion->estado ? 'Activa' : 'Inactiva',
        ];

        // return view('direcciones.mostrar', compact('registro'));
        return response()->json([
            'success' => true,
            'message' => 'Detalles de la dirección recuperados correctamente.',
            'data' => $registro,
        ], 200);
    }

    // Cambié actualizar por update(Request $request, $id) usando 'sometimes', imagen opcional en ImgBB y respondiendo en JSON.
    public function update(Request $request, $id)
    {
        $direccion = Direccion::find($id);

        if (!$direccion) {
            return response()->json([
                'success' => false,
                'message' => "No se puede actualizar: la dirección con ID [{$id}] no existe en la base de datos.",
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'cliente_id' => ['sometimes', 'required', 'exists:clientes,id'],
            'calle' => ['sometimes', 'required', 'string', 'max:255'],
            'numero' => ['sometimes', 'required', 'string', 'max:50'],
            'colonia' => ['sometimes', 'required', 'string', 'max:255'],
            'ciudad' => ['sometimes', 'required', 'string', 'max:255'],
            'codigo_postal' => ['sometimes', 'required', 'string', 'size:5'],
            'referencia' => ['nullable', 'string', 'max:255'],
            'estado' => ['nullable'],
            'imagen' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,gif,bmp', 'max:10240'],
        ], [
            'cliente_id.required' => 'Debe seleccionar un cliente.',
            'cliente_id.exists' => 'El cliente seleccionado no existe.',
            'calle.required' => 'La calle es obligatoria.',
            'numero.required' => 'El número es obligatorio.',
            'colonia.required' => 'La colonia es obligatoria.',
            'ciudad.required' => 'La ciudad es obligatoria.',
            'codigo_postal.size' => 'El código postal debe tener 5 caracteres.',
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
            $datos['estado'] = $this->parsearEstado($request->input('estado'), (bool) $direccion->estado);
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

        // $this->actualizarDatoTemporal('direcciones', $this->datos, $id, $datos);
        $direccion->update($datos);

        // return redirect()->route('direcciones.listar')->with('success', 'Dirección actualizada exitosamente.');
        return response()->json([
            'success' => true,
            'message' => 'Dirección actualizada exitosamente.',
            'data' => $direccion,
        ], 200);
    }

    // Cambié borrar por destroy(Request $request, $id) aplicando borrado lógico en lugar de sesión temporal para cuidar la relación con entregas.
    public function destroy(Request $request, $id)
    {
        $direccion = Direccion::find($id);

        if (!$direccion) {
            return response()->json([
                'success' => false,
                'message' => "No se puede eliminar: la dirección con ID [{$id}] no existe en la base de datos.",
            ], 404);
        }

        // $this->borrarDatoTemporal('direcciones', $this->datos, $id);
        $direccion->estado = false;
        $direccion->save();

        // return redirect()->route('direcciones.listar')->with('success', 'Dirección eliminada exitosamente.');
        return response()->json([
            'success' => true,
            'message' => 'Dirección desactivada correctamente (borrado lógico).',
            'data' => $direccion,
        ], 200);
    }
}
