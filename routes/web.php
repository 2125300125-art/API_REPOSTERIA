<?php

use Illuminate\Support\Facades\Route;

// Dejé activa únicamente esta ruta raíz en JSON para verificar que el servidor de mi API REST está corriendo,
// y comenté abajo las rutas web con vistas Blade del proyecto anterior para mostrar el cambio hacia routes/api.php.
Route::get('/', function () {
    return response()->json([
        'proyecto' => 'API REST - Distribuidora de Agua Potable',
        'version' => '1.0',
        'estado' => 'Activo',
    ]);
});

/*
|--------------------------------------------------------------------------
| Rutas Web Anteriores (Comentadas al pasar el proyecto a API REST)
|--------------------------------------------------------------------------
| Comenté estas rutas web porque los controladores del área administrativa,
| el inicio de sesión y las APIs adicionales ahora están ruteados en routes/api.php devolviendo JSON.
*/

// use App\Http\Controllers\AdministradorController;
// use App\Http\Controllers\CategoriaController;
// use App\Http\Controllers\ClienteController;
// use App\Http\Controllers\DireccionController;
// use App\Http\Controllers\EntregaController;
// use App\Http\Controllers\MarcaController;
// use App\Http\Controllers\MetodoPagoController;
// use App\Http\Controllers\PedidoController;
// use App\Http\Controllers\PresentacionController;
// use App\Http\Controllers\ProductoPedidoController;
// use App\Http\Controllers\ProductoController;
// use App\Http\Controllers\RepartidorController;
// use App\Http\Controllers\RolController;
// use App\Http\Controllers\UserController;
// use App\Http\Controllers\GeolocalizacionController;
// use App\Http\Controllers\PronosticoController;
// use App\Http\Controllers\DolarController;
// use App\Http\Controllers\LoginController;
// use App\Http\Controllers\GoogleController;

// // Rutas de autenticación web anteriores
// Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
// Route::post('/login', [LoginController::class, 'login'])->name('login.post');
// Route::get('/auth/google', [GoogleController::class, 'redirectToGoogle'])->name('google.login');
// Route::get('/auth/google/callback', [GoogleController::class, 'handleGoogleCallback'])->name('google.callback');

// Route::middleware(['auth:admin'])->group(function () {
//     // Cerrar sesión
//     Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

//     // Administradores
//     Route::get('/administradores/listar', [AdministradorController::class, 'listar'])->name('administradores.listar');
//     Route::get('/administradores/crear', [AdministradorController::class, 'vistaFormulario'])->name('administradores.crear');
//     Route::post('/administradores/guardar', [AdministradorController::class, 'registrar'])->name('administradores.guardar');
//     Route::get('/administradores/editar/{id?}', [AdministradorController::class, 'vistaEdicion'])->name('administradores.editar');
//     Route::put('/administradores/actualizar/{id?}', [AdministradorController::class, 'actualizar'])->name('administradores.actualizar');
//     Route::get('/administradores/mostrar/{id?}', [AdministradorController::class, 'vistaMostrar'])->name('administradores.mostrar');
//     Route::delete('/administradores/borrar/{id?}', [AdministradorController::class, 'borrar'])->name('administradores.borrar');

//     // Productos
//     Route::get('/productos', [ProductoController::class, 'listar'])->name('productos.index');
//     Route::get('/productos/create', [ProductoController::class, 'create'])->name('productos.create');
//     Route::post('/productos', [ProductoController::class, 'store'])->name('productos.store');
//     Route::get('/productos/listar', [ProductoController::class, 'listar'])->name('productos.listar');
//     Route::get('/productos/crear', [ProductoController::class, 'create'])->name('productos.crear');
//     Route::post('/productos/guardar', [ProductoController::class, 'store'])->name('productos.guardar');
//     Route::get('/productos/editar/{id?}', [ProductoController::class, 'vistaEdicion'])->name('productos.editar');
//     Route::put('/productos/actualizar/{id?}', [ProductoController::class, 'actualizar'])->name('productos.actualizar');
//     Route::get('/productos/mostrar/{id?}', [ProductoController::class, 'vistaMostrar'])->name('productos.mostrar');
//     Route::delete('/productos/borrar/{id?}', [ProductoController::class, 'borrar'])->name('productos.borrar');

//     // Clientes
//     Route::get('/clientes', [ClienteController::class, 'listar'])->name('clientes.index');
//     Route::get('/clientes/create', [ClienteController::class, 'create'])->name('clientes.create');
//     Route::post('/clientes', [ClienteController::class, 'store'])->name('clientes.store');
//     Route::get('/clientes/listar', [ClienteController::class, 'listar'])->name('clientes.listar');
//     Route::get('/clientes/crear', [ClienteController::class, 'create'])->name('clientes.crear');
//     Route::post('/clientes/guardar', [ClienteController::class, 'store'])->name('clientes.guardar');
//     Route::get('/clientes/editar/{id?}', [ClienteController::class, 'vistaEdicion'])->name('clientes.editar');
//     Route::put('/clientes/actualizar/{id?}', [ClienteController::class, 'actualizar'])->name('clientes.actualizar');
//     Route::get('/clientes/mostrar/{id?}', [ClienteController::class, 'vistaMostrar'])->name('clientes.mostrar');
//     Route::delete('/clientes/borrar/{id?}', [ClienteController::class, 'borrar'])->name('clientes.borrar');

//     // Pedidos
//     Route::get('/pedidos/listar', [PedidoController::class, 'listar'])->name('pedidos.listar');
//     Route::get('/pedidos/crear', [PedidoController::class, 'vistaFormulario'])->name('pedidos.crear');
//     Route::post('/pedidos/guardar', [PedidoController::class, 'registrar'])->name('pedidos.guardar');
//     Route::get('/pedidos/editar/{id?}', [PedidoController::class, 'vistaEdicion'])->name('pedidos.editar');
//     Route::put('/pedidos/actualizar/{id?}', [PedidoController::class, 'actualizar'])->name('pedidos.actualizar');
//     Route::get('/pedidos/mostrar/{id?}', [PedidoController::class, 'vistaMostrar'])->name('pedidos.mostrar');
//     Route::delete('/pedidos/borrar/{id?}', [PedidoController::class, 'borrar'])->name('pedidos.borrar');

//     // Repartidores
//     Route::get('/repartidores/listar', [RepartidorController::class, 'listar'])->name('repartidores.listar');
//     Route::get('/repartidores/crear', [RepartidorController::class, 'vistaFormulario'])->name('repartidores.crear');
//     Route::post('/repartidores/guardar', [RepartidorController::class, 'registrar'])->name('repartidores.guardar');
//     Route::get('/repartidores/editar/{id?}', [RepartidorController::class, 'vistaEdicion'])->name('repartidores.editar');
//     Route::put('/repartidores/actualizar/{id?}', [RepartidorController::class, 'actualizar'])->name('repartidores.actualizar');
//     Route::get('/repartidores/mostrar/{id?}', [RepartidorController::class, 'vistaMostrar'])->name('repartidores.mostrar');
//     Route::delete('/repartidores/borrar/{id?}', [RepartidorController::class, 'borrar'])->name('repartidores.borrar');

//     // Entregas
//     Route::get('/entregas/listar', [EntregaController::class, 'listar'])->name('entregas.listar');
//     Route::get('/entregas/crear', [EntregaController::class, 'vistaFormulario'])->name('entregas.crear');
//     Route::post('/entregas/guardar', [EntregaController::class, 'registrar'])->name('entregas.guardar');
//     Route::get('/entregas/editar/{id?}', [EntregaController::class, 'vistaEdicion'])->name('entregas.editar');
//     Route::put('/entregas/actualizar/{id?}', [EntregaController::class, 'actualizar'])->name('entregas.actualizar');
//     Route::get('/entregas/mostrar/{id?}', [EntregaController::class, 'vistaMostrar'])->name('entregas.mostrar');
//     Route::delete('/entregas/borrar/{id?}', [EntregaController::class, 'borrar'])->name('entregas.borrar');

//     // Categorías
//     Route::get('/categorias', [CategoriaController::class, 'listar'])->name('categorias.index');
//     Route::get('/categorias/create', [CategoriaController::class, 'create'])->name('categorias.create');
//     Route::post('/categorias', [CategoriaController::class, 'store'])->name('categorias.store');
//     Route::get('/categorias/listar', [CategoriaController::class, 'listar'])->name('categorias.listar');
//     Route::get('/categorias/crear', [CategoriaController::class, 'create'])->name('categorias.crear');
//     Route::post('/categorias/guardar', [CategoriaController::class, 'store'])->name('categorias.guardar');
//     Route::get('/categorias/editar/{id?}', [CategoriaController::class, 'vistaEdicion'])->name('categorias.editar');
//     Route::put('/categorias/actualizar/{id?}', [CategoriaController::class, 'actualizar'])->name('categorias.actualizar');
//     Route::get('/categorias/mostrar/{id?}', [CategoriaController::class, 'vistaMostrar'])->name('categorias.mostrar');
//     Route::delete('/categorias/borrar/{id?}', [CategoriaController::class, 'borrar'])->name('categorias.borrar');

//     // Marcas
//     Route::get('/marcas', [MarcaController::class, 'listar'])->name('marcas.index');
//     Route::get('/marcas/create', [MarcaController::class, 'create'])->name('marcas.create');
//     Route::post('/marcas', [MarcaController::class, 'store'])->name('marcas.store');
//     Route::get('/marcas/listar', [MarcaController::class, 'listar'])->name('marcas.listar');
//     Route::get('/marcas/crear', [MarcaController::class, 'create'])->name('marcas.crear');
//     Route::post('/marcas/guardar', [MarcaController::class, 'store'])->name('marcas.guardar');
//     Route::get('/marcas/editar/{id?}', [MarcaController::class, 'vistaEdicion'])->name('marcas.editar');
//     Route::put('/marcas/actualizar/{id?}', [MarcaController::class, 'actualizar'])->name('marcas.actualizar');
//     Route::get('/marcas/mostrar/{id?}', [MarcaController::class, 'vistaMostrar'])->name('marcas.mostrar');
//     Route::delete('/marcas/borrar/{id?}', [MarcaController::class, 'borrar'])->name('marcas.borrar');

//     // Presentaciones
//     Route::get('/presentaciones', [PresentacionController::class, 'listar'])->name('presentaciones.index');
//     Route::get('/presentaciones/create', [PresentacionController::class, 'create'])->name('presentaciones.create');
//     Route::post('/presentaciones', [PresentacionController::class, 'store'])->name('presentaciones.store');
//     Route::get('/presentaciones/listar', [PresentacionController::class, 'listar'])->name('presentaciones.listar');
//     Route::get('/presentaciones/crear', [PresentacionController::class, 'create'])->name('presentaciones.crear');
//     Route::post('/presentaciones/guardar', [PresentacionController::class, 'store'])->name('presentaciones.guardar');
//     Route::get('/presentaciones/editar/{id?}', [PresentacionController::class, 'vistaEdicion'])->name('presentaciones.editar');
//     Route::put('/presentaciones/actualizar/{id?}', [PresentacionController::class, 'actualizar'])->name('presentaciones.actualizar');
//     Route::get('/presentaciones/mostrar/{id?}', [PresentacionController::class, 'vistaMostrar'])->name('presentaciones.mostrar');
//     Route::delete('/presentaciones/borrar/{id?}', [PresentacionController::class, 'borrar'])->name('presentaciones.borrar');

//     // Direcciones
//     Route::get('/direcciones/listar', [DireccionController::class, 'listar'])->name('direcciones.listar');
//     Route::get('/direcciones/crear', [DireccionController::class, 'vistaFormulario'])->name('direcciones.crear');
//     Route::post('/direcciones/guardar', [DireccionController::class, 'registrar'])->name('direcciones.guardar');
//     Route::get('/direcciones/editar/{id?}', [DireccionController::class, 'vistaEdicion'])->name('direcciones.editar');
//     Route::put('/direcciones/actualizar/{id?}', [DireccionController::class, 'actualizar'])->name('direcciones.actualizar');
//     Route::get('/direcciones/mostrar/{id?}', [DireccionController::class, 'vistaMostrar'])->name('direcciones.mostrar');
//     Route::delete('/direcciones/borrar/{id?}', [DireccionController::class, 'borrar'])->name('direcciones.borrar');

//     // Métodos de Pago
//     Route::get('/metodos_pago/listar', [MetodoPagoController::class, 'listar'])->name('metodos_pago.listar');
//     Route::get('/metodos_pago/crear', [MetodoPagoController::class, 'vistaFormulario'])->name('metodos_pago.crear');
//     Route::post('/metodos_pago/guardar', [MetodoPagoController::class, 'registrar'])->name('metodos_pago.guardar');
//     Route::get('/metodos_pago/editar/{id?}', [MetodoPagoController::class, 'vistaEdicion'])->name('metodos_pago.editar');
//     Route::put('/metodos_pago/actualizar/{id?}', [MetodoPagoController::class, 'actualizar'])->name('metodos_pago.actualizar');
//     Route::get('/metodos_pago/mostrar/{id?}', [MetodoPagoController::class, 'vistaMostrar'])->name('metodos_pago.mostrar');
//     Route::delete('/metodos_pago/borrar/{id?}', [MetodoPagoController::class, 'borrar'])->name('metodos_pago.borrar');

//     // Roles
//     Route::get('/roles/listar', [RolController::class, 'listar'])->name('roles.listar');
//     Route::get('/roles/crear', [RolController::class, 'vistaFormulario'])->name('roles.crear');
//     Route::post('/roles/guardar', [RolController::class, 'registrar'])->name('roles.guardar');
//     Route::get('/roles/editar/{id?}', [RolController::class, 'vistaEdicion'])->name('roles.editar');
//     Route::put('/roles/actualizar/{id?}', [RolController::class, 'actualizar'])->name('roles.actualizar');
//     Route::get('/roles/mostrar/{id?}', [RolController::class, 'vistaMostrar'])->name('roles.mostrar');
//     Route::delete('/roles/borrar/{id?}', [RolController::class, 'borrar'])->name('roles.borrar');

//     // Productos por Pedido
//     Route::get('/productos_pedido/listar', [ProductoPedidoController::class, 'listar'])->name('productos_pedido.listar');
//     Route::get('/productos_pedido/crear', [ProductoPedidoController::class, 'vistaFormulario'])->name('productos_pedido.crear');
//     Route::post('/productos_pedido/guardar', [ProductoPedidoController::class, 'registrar'])->name('productos_pedido.guardar');
//     Route::get('/productos_pedido/editar/{id?}', [ProductoPedidoController::class, 'vistaEdicion'])->name('productos_pedido.editar');
//     Route::put('/productos_pedido/actualizar/{id?}', [ProductoPedidoController::class, 'actualizar'])->name('productos_pedido.actualizar');
//     Route::get('/productos_pedido/mostrar/{id?}', [ProductoPedidoController::class, 'vistaMostrar'])->name('productos_pedido.mostrar');
//     Route::delete('/productos_pedido/borrar/{id?}', [ProductoPedidoController::class, 'borrar'])->name('productos_pedido.borrar');

//     // Usuarios
//     Route::get('/users/listar', [UserController::class, 'listar'])->name('users.listar');
//     Route::get('/users/crear', [UserController::class, 'vistaFormulario'])->name('users.crear');
//     Route::post('/users/guardar', [UserController::class, 'registrar'])->name('users.guardar');
//     Route::get('/users/editar/{id?}', [UserController::class, 'vistaEdicion'])->name('users.editar');
//     Route::put('/users/actualizar/{id?}', [UserController::class, 'actualizar'])->name('users.actualizar');
//     Route::get('/users/mostrar/{id?}', [UserController::class, 'vistaMostrar'])->name('users.mostrar');
//     Route::delete('/users/borrar/{id?}', [UserController::class, 'borrar'])->name('users.borrar');

//     // APIs adicionales
//     Route::get('/ubicacion', [GeolocalizacionController::class, 'obtenerUbicacion'])->name('geolocalizacion.obtenerUbicacion');
//     Route::get('/pronostico', [PronosticoController::class, 'obtenerPronostico'])->name('pronostico.obtenerPronostico');
//     Route::get('/dolar', [DolarController::class, 'obtenerDolar'])->name('dolar.obtenerDolar');
// });
