<?php

namespace App\Http\Controllers;

use App\Models\Administrador;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AdministradorController extends Controller
{
    private const MODELO = Administrador::class;

    // Comenté el arreglo de datos fijos que tenía de prueba al inicio porque ahora leo y guardo directo en la tabla administradores.
    // private array $datos = [
    //     ['id' => 1, 'nombre' => 'Mariana', 'apellidos' => 'López Rivera', 'correo' => 'mariana.lopez@aguaclara.test', 'usuario' => 'mlopez', 'rol' => 'Administradora', 'telefono' => '5551234567', 'estado' => 'Activo'],
    //     ['id' => 2, 'nombre' => 'Jorge', 'apellidos' => 'Sánchez Mora', 'correo' => 'jorge.sanchez@aguaclara.test', 'usuario' => 'jsanchez', 'rol' => 'Operador', 'telefono' => '5559876543', 'estado' => 'Activo'],
    // ];

    // Cambié mi función listar() por index(Request $request) y comenté la vista Blade para devolver el JSON de administradores con success y data.
    public function index(Request $request)
    {
        $administradores = Administrador::query()->with('rol')->get()->map(fn (Administrador $administrador) => [
            'id' => $administrador->id,
            'nombre' => $administrador->nombre,
            'apellidos' => $administrador->apellidos,
            'correo' => $administrador->correo,
            'usuario' => $administrador->usuario,
            'rol_id' => $administrador->rol_id,
            'rol' => $administrador->rol->nombre ?? 'Sin rol asignado',
            'imagen' => $administrador->imagen,
            'estado' => $administrador->activo ? 'Activo' : 'Inactivo',
        ])->all();

        // return view('administradores.listado', compact('administradores'));
        return response()->json([
            'success' => true,
            'message' => 'Administradores recuperados correctamente.',
            'data' => $administradores,
        ], 200);
    }

    // Comenté vistaFormulario y vistaEdicion porque en la API ya no cargo formularios Blade.
    // public function vistaFormulario()
    // {
    //     return view('administradores.formulario');
    // }

    // Antes en registrar() guardaba en sesión temporal; lo cambié a store(Request $request) validando y guardando en MySQL con su imagen en ImgBB.
    public function store(Request $request)
    {
        // $this->registrarDatoTemporal('administradores', $this->datos, $request);
        // return redirect()->route('administradores.listar')->with('success', 'Administrador agregado exitosamente.');

        $validator = Validator::make($request->all(), [
            'nombre' => ['required', 'string', 'max:255'],
            'apellidos' => ['required', 'string', 'max:255'],
            'correo' => ['required', 'email', 'max:255', 'unique:administradores,correo'],
            'usuario' => ['required', 'string', 'max:255', 'unique:administradores,usuario'],
            'contraseña' => ['required', 'string', 'min:8'],
            'rol_id' => ['required', 'exists:roles,id'],
            'activo' => ['nullable'],
            'imagen' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,gif,bmp', 'max:10240'],
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'apellidos.required' => 'Los apellidos son obligatorios.',
            'correo.required' => 'El correo es obligatorio.',
            'correo.unique' => 'Este correo ya pertenece a otro administrador.',
            'usuario.required' => 'El usuario es obligatorio.',
            'usuario.unique' => 'Este nombre de usuario ya está en uso.',
            'contraseña.required' => 'La contraseña es obligatoria.',
            'rol_id.required' => 'El rol es obligatorio.',
            'rol_id.exists' => 'El rol seleccionado no existe.',
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

        $datos['contraseña'] = Hash::make($datos['contraseña']);
        $datos['activo'] = $request->has('activo')
            ? $this->parsearEstado($request->input('activo'), true)
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

        $admin = Administrador::create($datos);

        return response()->json([
            'success' => true,
            'message' => 'Administrador registrado exitosamente.',
            'data' => $admin,
        ], 201);
    }

    // public function vistaEdicion($id = null)
    // {
    //     $admin = Administrador::find($id);
    //     if (!$admin) {
    //         return redirect()->route('administradores.listar');
    //     }
    //     $roles = \App\Models\Rol::all();
    //     return view('administradores.edicion', compact('admin', 'roles'));
    // }

    // Cambié vistaMostrar por show(Request $request, $id) y comenté el redirect y la vista para retornar JSON.
    public function show(Request $request, $id)
    {
        // 1. Validar que el ID exista en la base de datos y cargar la relación con la tabla roles
        $admin = Administrador::with('rol')->find($id);

        if (!$admin) {
            // return redirect()->route('administradores.listar')
            //     ->with('error', "El administrador con ID [{$id}] no existe en la base de datos.");
            return response()->json([
                'success' => false,
                'message' => "El administrador con ID [{$id}] no existe en la base de datos.",
            ], 404);
        }

        // 2. Preparar arreglo con el nombre del rol en lugar del ID foráneo (rol_id)
        $registro = [
            'id' => $admin->id,
            'nombre' => $admin->nombre,
            'apellidos' => $admin->apellidos,
            'correo' => $admin->correo,
            'usuario' => $admin->usuario,
            'rol_id' => $admin->rol_id,
            'rol' => $admin->rol ? $admin->rol->nombre : 'Sin rol asignado',
            'imagen' => $admin->imagen,
            'estado' => $admin->activo ? 'Activo' : 'Inactivo',
        ];

        // return view('administradores.mostrar', compact('admin', 'registro'));
        return response()->json([
            'success' => true,
            'message' => 'Detalles del administrador recuperados correctamente.',
            'data' => $registro,
        ], 200);
    }

    // Cambié actualizar por update(Request $request, $id) usando 'sometimes' para soportar cambios parciales o completos por PUT desde Postman.
    public function update(Request $request, $id)
    {
        // 1. Validar que el ID exista en la base de datos
        $admin = Administrador::find($id);

        if (!$admin) {
            // return redirect()->route('administradores.listar')
            //     ->with('error', "No se puede actualizar: el administrador con ID [{$id}] no existe en la base de datos.");
            return response()->json([
                'success' => false,
                'message' => "No se puede actualizar: el administrador con ID [{$id}] no existe en la base de datos.",
            ], 404);
        }

        // 2. Validación de datos de entrada con Validator y regla 'sometimes'
        $validator = Validator::make($request->all(), [
            'nombre' => ['sometimes', 'required', 'string', 'max:255'],
            'apellidos' => ['sometimes', 'required', 'string', 'max:255'],
            'correo' => ['sometimes', 'required', 'email', 'max:255', 'unique:administradores,correo,' . $id],
            'usuario' => ['sometimes', 'required', 'string', 'max:255', 'unique:administradores,usuario,' . $id],
            'rol_id' => ['sometimes', 'required', 'exists:roles,id'],
            'contraseña' => ['nullable', 'string', 'min:8'],
            'activo' => ['nullable'],
            'imagen' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,gif,bmp', 'max:10240'],
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'apellidos.required' => 'Los apellidos son obligatorios.',
            'correo.required' => 'El correo es obligatorio.',
            'correo.unique' => 'Este correo ya pertenece a otro administrador.',
            'usuario.required' => 'El usuario es obligatorio.',
            'usuario.unique' => 'Este nombre de usuario ya está en uso.',
            'rol_id.required' => 'El rol es obligatorio.',
            'rol_id.exists' => 'El rol seleccionado no existe.',
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

        if ($request->has('activo')) {
            $datos['activo'] = $this->parsearEstado($request->input('activo'), (bool) $admin->activo);
        } else {
            unset($datos['activo']);
        }

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

        $admin->update($datos);

        // return redirect()->route('administradores.listar')->with('success', 'Administrador actualizado exitosamente.');
        return response()->json([
            'success' => true,
            'message' => 'Administrador actualizado exitosamente.',
            'data' => $admin,
        ], 200);
    }

    // Cambié borrar por destroy(Request $request, $id) manteniendo el borrado lógico sobre el campo 'activo' y respondiendo JSON.
    public function destroy(Request $request, $id)
    {
        // 1. Validar que el ID exista antes del proceso de borrado
        $admin = Administrador::find($id);

        if (!$admin) {
            // return redirect()->route('administradores.listar')
            //     ->with('error', "No se puede eliminar: el administrador con ID [{$id}] no existe en la base de datos.");
            return response()->json([
                'success' => false,
                'message' => "No se puede eliminar: el administrador con ID [{$id}] no existe en la base de datos.",
            ], 404);
        }

        // 2. Borrado lógico: la tabla administradores tiene el campo booleano 'activo'
        $admin->activo = false;
        $admin->save();

        // return redirect()->route('administradores.listar')->with('success', "El administrador '{$admin->nombre} {$admin->apellidos}' fue dado de baja (Inactivo) correctamente.");
        return response()->json([
            'success' => true,
            'message' => "El administrador '{$admin->nombre} {$admin->apellidos}' fue desactivado correctamente (borrado lógico).",
            'data' => $admin,
        ], 200);
    }
}
