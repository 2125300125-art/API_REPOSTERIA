<?php

namespace App\Http\Controllers;

use App\Models\Repartidor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RepartidorController extends Controller
{
    private const MODELO = Repartidor::class;

    // Cambié listar() por index(Request $request) comentando la vista Blade para devolver el listado de repartidores en JSON con success y data.
    public function index(Request $request)
    {
        $repartidores = Repartidor::query()->get()->map(fn (Repartidor $repartidor) => [
            'id' => $repartidor->id,
            'nombres' => $repartidor->nombres,
            'apellidos' => $repartidor->apellidos,
            'telefono' => $repartidor->telefono,
            'licencia' => $repartidor->licencia,
            'imagen' => $repartidor->imagen,
            'estado' => $repartidor->estado ? 'Activo' : 'Inactivo',
        ])->all();

        // return view('repartidores.listado', compact('repartidores'));
        return response()->json([
            'success' => true,
            'message' => 'Repartidores recuperados correctamente.',
            'data' => $repartidores,
        ], 200);
    }

    // Comenté vistaFormulario y vistaEdicion porque en la API REST no ocupo formularios Blade.
    // public function vistaFormulario()
    // {
    //     return view('repartidores.formulario');
    // }

    // Cambié registrar() por store(Request $request) validando teléfono de 10 dígitos, licencia única, imagen opcional y respuesta JSON 201.
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombres' => ['required', 'string', 'max:255'],
            'apellidos' => ['required', 'string', 'max:255'],
            'telefono' => ['required', 'string', 'digits:10'],
            'licencia' => ['required', 'string', 'max:255', 'unique:repartidores,licencia'],
            'estado' => ['nullable'],
            'imagen' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,gif,bmp', 'max:10240'],
        ], [
            'nombres.required' => 'El nombre es obligatorio.',
            'apellidos.required' => 'Los apellidos son obligatorios.',
            'telefono.digits' => 'El teléfono debe tener 10 dígitos numéricos.',
            'licencia.required' => 'La licencia es obligatoria.',
            'licencia.unique' => 'Ya existe otro repartidor con esta licencia.',
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

        $repartidor = Repartidor::create($datos);

        // return redirect()->route('repartidores.listar')->with('success', 'Repartidor registrado exitosamente.');
        return response()->json([
            'success' => true,
            'message' => 'Repartidor registrado exitosamente.',
            'data' => $repartidor,
        ], 201);
    }

    // public function vistaEdicion($id = 1)
    // {
    //     $rep = Repartidor::find($id) ?? Repartidor::first();
    //     return view('repartidores.edicion', ['registro' => $rep]);
    // }

    // Cambié vistaMostrar por show(Request $request, $id) quitando Repartidor::first() para responder con 404 real si el ID no existe.
    public function show(Request $request, $id)
    {
        // $repartidor = Repartidor::find($id) ?? Repartidor::first();
        $repartidor = Repartidor::find($id);

        if (!$repartidor) {
            return response()->json([
                'success' => false,
                'message' => "El repartidor con ID [{$id}] no existe en la base de datos.",
            ], 404);
        }

        $registro = [
            'id' => $repartidor->id,
            'nombres' => $repartidor->nombres,
            'apellidos' => $repartidor->apellidos,
            'telefono' => $repartidor->telefono,
            'licencia' => $repartidor->licencia,
            'imagen' => $repartidor->imagen,
            'estado' => $repartidor->estado ? 'Activo' : 'Inactivo',
        ];

        // return view('repartidores.mostrar', ['registro' => $registro]);
        return response()->json([
            'success' => true,
            'message' => 'Detalles del repartidor recuperados correctamente.',
            'data' => $registro,
        ], 200);
    }

    // Cambié actualizar por update(Request $request, $id) usando 'sometimes', imagen por Request y respondiendo en JSON.
    public function update(Request $request, $id)
    {
        $repartidor = Repartidor::find($id);

        if (!$repartidor) {
            return response()->json([
                'success' => false,
                'message' => "No se puede actualizar: el repartidor con ID [{$id}] no existe en la base de datos.",
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nombres' => ['sometimes', 'required', 'string', 'max:255'],
            'apellidos' => ['sometimes', 'required', 'string', 'max:255'],
            'telefono' => ['sometimes', 'required', 'string', 'digits:10'],
            'licencia' => ['sometimes', 'required', 'string', 'max:255', 'unique:repartidores,licencia,' . $id],
            'estado' => ['nullable'],
            'imagen' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,gif,bmp', 'max:10240'],
        ], [
            'nombres.required' => 'El nombre es obligatorio.',
            'apellidos.required' => 'Los apellidos son obligatorios.',
            'telefono.digits' => 'El teléfono debe tener 10 dígitos numéricos.',
            'licencia.required' => 'La licencia es obligatoria.',
            'licencia.unique' => 'Ya existe otro repartidor con esta licencia.',
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
            $datos['estado'] = $this->parsearEstado($request->input('estado'), (bool) $repartidor->estado);
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

        $repartidor->update($datos);

        // return redirect()->route('repartidores.listar')->with('success', 'Repartidor actualizado exitosamente.');
        return response()->json([
            'success' => true,
            'message' => 'Repartidor actualizado exitosamente.',
            'data' => $repartidor,
        ], 200);
    }

    // Cambié borrar por destroy(Request $request, $id) comentando delete() físico para usar borrado lógico porque entregas referencia a repartidores.
    public function destroy(Request $request, $id)
    {
        $repartidor = Repartidor::find($id);

        if (!$repartidor) {
            return response()->json([
                'success' => false,
                'message' => "No se puede eliminar: el repartidor con ID [{$id}] no existe en la base de datos.",
            ], 404);
        }

        // $repartidor->delete();
        $repartidor->estado = false;
        $repartidor->save();

        // return redirect()->route('repartidores.listar')->with('success', 'Repartidor eliminado exitosamente.');
        return response()->json([
            'success' => true,
            'message' => "El repartidor '{$repartidor->nombres} {$repartidor->apellidos}' fue desactivado correctamente (borrado lógico).",
            'data' => $repartidor,
        ], 200);
    }
}
