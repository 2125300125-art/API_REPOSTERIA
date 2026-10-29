<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ClienteController extends Controller
{
    private const MODELO = Cliente::class;

    // Cambié listar() por index(Request $request) y comenté el retorno a la vista Blade para devolver el JSON de clientes con success y data.
    public function index(Request $request)
    {
        $clientes = Cliente::query()->get(['id', 'nombres', 'apellidos', 'correo', 'telefono', 'imagen', 'estado'])->map(fn (Cliente $cliente) => [
            'id' => $cliente->id,
            'nombres' => $cliente->nombres,
            'apellidos' => $cliente->apellidos,
            'correo' => $cliente->correo,
            'telefono' => $cliente->telefono,
            'imagen' => $cliente->imagen,
            'estado' => $cliente->estado ? 'Activo' : 'Inactivo',
        ])->all();

        // return view('clientes.listado', compact('clientes'));
        return response()->json([
            'success' => true,
            'message' => 'Clientes recuperados correctamente.',
            'data' => $clientes,
        ], 200);
    }

    // Comenté los métodos de formularios HTML porque en la API envío los datos por POST desde Postman.
    // public function vistaFormulario() { return $this->create(); }
    // public function create() { return view('clientes.create'); }
    // public function registrar(Request $request) { return $this->store($request); }

    // Adapté store para responder en JSON (201 o 422) conservando el hash de contraseña y la subida de imagen a ImgBB.
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombres' => ['required', 'string', 'max:255'],
            'apellidos' => ['required', 'string', 'max:255'],
            'correo' => ['required', 'email', 'max:255', 'unique:clientes,correo'],
            'contraseña' => ['required', 'string', 'min:8', 'max:255'],
            'telefono' => ['required', 'string', 'digits:10'],
            'estado' => ['nullable'],
            'imagen' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,gif,bmp', 'max:10240'],
        ], [
            'nombres.required' => 'El nombre es obligatorio.',
            'apellidos.required' => 'Los apellidos son obligatorios.',
            'correo.required' => 'El correo electrónico es obligatorio.',
            'correo.unique' => 'Ya existe otro cliente con este correo.',
            'contraseña.required' => 'La contraseña es obligatoria.',
            'telefono.digits' => 'El teléfono debe tener 10 dígitos numéricos.',
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

        $datos['contraseña'] = Hash::make($datos['contraseña']);
        $datos['estado'] = $request->has('estado')
            ? $this->parsearEstado($request->input('estado'), true)
            : true;

        if ($request->hasFile('imagen')) {
            try {
                $datos['imagen'] = $this->subirImagenOpcional($request);
            } catch (\Exception $e) {
                // return redirect()->back()->withErrors(['imagen' => $e->getMessage()])->withInput();
                return response()->json([
                    'success' => false,
                    'message' => 'Error al subir la imagen: ' . $e->getMessage(),
                ], 500);
            }
        }

        $cliente = Cliente::create($datos);

        // return redirect()->route('clientes.index')->with('mensaje', 'Registro guardado exitosamente.');
        return response()->json([
            'success' => true,
            'message' => 'Cliente registrado exitosamente.',
            'data' => $cliente,
        ], 201);
    }

    // public function vistaEdicion($id = null)
    // {
    //     $cliente = Cliente::find($id);
    //     if (!$cliente) { return redirect()->route('clientes.listar'); }
    //     return view('clientes.edicion', compact('cliente'));
    // }

    // Cambié vistaMostrar por show(Request $request, $id) y comenté la vista para devolver JSON.
    public function show(Request $request, $id)
    {
        // 1. Validar que el ID exista en la base de datos
        $cliente = Cliente::find($id);

        if (!$cliente) {
            // return redirect()->route('clientes.listar')
            //     ->with('error', "El cliente con ID [{$id}] no existe en la base de datos.");
            return response()->json([
                'success' => false,
                'message' => "El cliente con ID [{$id}] no existe en la base de datos.",
            ], 404);
        }

        $registro = [
            'id' => $cliente->id,
            'nombres' => $cliente->nombres,
            'apellidos' => $cliente->apellidos,
            'correo' => $cliente->correo,
            'telefono' => $cliente->telefono,
            'imagen' => $cliente->imagen,
            'estado' => $cliente->estado ? 'Activo' : 'Inactivo',
        ];

        // return view('clientes.mostrar', compact('cliente', 'registro'));
        return response()->json([
            'success' => true,
            'message' => 'Detalles del cliente recuperados correctamente.',
            'data' => $registro,
        ], 200);
    }

    // Cambié actualizar por update(Request $request, $id) usando 'sometimes', comentando los redirects web y respondiendo JSON.
    public function update(Request $request, $id)
    {
        // 1. Validar que el ID exista en la base de datos
        $cliente = Cliente::find($id);

        if (!$cliente) {
            // return redirect()->route('clientes.listar')
            //     ->with('error', "No se puede actualizar: el cliente con ID [{$id}] no existe en la base de datos.");
            return response()->json([
                'success' => false,
                'message' => "No se puede actualizar: el cliente con ID [{$id}] no existe en la base de datos.",
            ], 404);
        }

        // 2. Validación de datos de entrada con Validator y regla 'sometimes'
        $validator = Validator::make($request->all(), [
            'nombres' => ['sometimes', 'required', 'string', 'max:255'],
            'apellidos' => ['sometimes', 'required', 'string', 'max:255'],
            'correo' => ['sometimes', 'required', 'email', 'max:255', 'unique:clientes,correo,' . $id],
            'telefono' => ['sometimes', 'required', 'string', 'digits:10'],
            'contraseña' => ['nullable', 'string', 'min:8', 'max:255'],
            'estado' => ['nullable'],
            'imagen' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,gif,bmp', 'max:10240'],
        ], [
            'nombres.required' => 'El nombre es obligatorio.',
            'apellidos.required' => 'Los apellidos son obligatorios.',
            'correo.required' => 'El correo electrónico es obligatorio.',
            'correo.unique' => 'Ya existe otro cliente con este correo.',
            'telefono.digits' => 'El teléfono debe tener 10 dígitos numéricos.',
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
        if ($request->filled('contraseña')) {
            $datos['contraseña'] = Hash::make($request->input('contraseña'));
        } else {
            unset($datos['contraseña']);
        }

        if ($request->has('estado')) {
            $datos['estado'] = $this->parsearEstado($request->input('estado'), (bool) $cliente->estado);
        } else {
            unset($datos['estado']);
        }

        // Subir nueva imagen a la API si seleccioné una en Postman
        if ($request->hasFile('imagen')) {
            try {
                $datos['imagen'] = $this->subirImagenOpcional($request);
            } catch (\Exception $e) {
                // return redirect()->back()->withErrors(['imagen' => $e->getMessage()])->withInput();
                return response()->json([
                    'success' => false,
                    'message' => 'Error al subir la imagen: ' . $e->getMessage(),
                ], 500);
            }
        } else {
            unset($datos['imagen']);
        }

        $cliente->update($datos);

        // return redirect()->route('clientes.listar')->with('success', 'Cliente actualizado exitosamente.');
        return response()->json([
            'success' => true,
            'message' => 'Cliente actualizado exitosamente.',
            'data' => $cliente,
        ], 200);
    }

    // Cambié borrar por destroy(Request $request, $id) conservando el borrado lógico y respondiendo en JSON.
    public function destroy(Request $request, $id)
    {
        // 1. Validar que el ID exista antes del proceso de borrado
        $cliente = Cliente::find($id);

        if (!$cliente) {
            // return redirect()->route('clientes.listar')
            //     ->with('error', "No se puede eliminar: el cliente con ID [{$id}] no existe en la base de datos.");
            return response()->json([
                'success' => false,
                'message' => "No se puede eliminar: el cliente con ID [{$id}] no existe en la base de datos.",
            ], 404);
        }

        // 2. Borrado lógico: la tabla clientes tiene el campo booleano 'estado' y clave foránea restrictOnDelete en pedidos y direcciones
        $cliente->estado = false;
        $cliente->save();

        // return redirect()->route('clientes.listar')->with('success', "El cliente '{$cliente->nombres} {$cliente->apellidos}' fue dado de baja (Inactivo) correctamente.");
        return response()->json([
            'success' => true,
            'message' => "El cliente '{$cliente->nombres} {$cliente->apellidos}' fue desactivado correctamente (borrado lógico).",
            'data' => $cliente,
        ], 200);
    }
}
