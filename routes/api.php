<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdministradorController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\DireccionController;
use App\Http\Controllers\DolarController;
use App\Http\Controllers\EntregaController;
use App\Http\Controllers\GeolocalizacionController;
use App\Http\Controllers\GoogleController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\MarcaController;
use App\Http\Controllers\MetodoPagoController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\PresentacionController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProductoPedidoController;
use App\Http\Controllers\PronosticoController;
use App\Http\Controllers\RepartidorController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Rutas de Autenticación de la API REST (Login con Sanctum, Perfil, Logout y Google)
Route::post('/login', [LoginController::class, 'login'])->name('api.login');
Route::get('/auth/google', [GoogleController::class, 'redirectToGoogle'])->name('api.google.login');
Route::get('/auth/google/callback', [GoogleController::class, 'handleGoogleCallback'])->name('api.google.callback');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::get('/perfil', [LoginController::class, 'perfil'])->name('api.perfil');
    Route::post('/logout', [LoginController::class, 'logout'])->name('api.logout');
});

// Aquí ruteé uno por uno los controladores de las tablas de mi Distribuidora de Agua Potable,
// conectando los métodos GET, POST, PUT y DELETE a las funciones index, show, store, update y destroy
// para probar todos los CRUD desde Postman (si se requiere protegerlos con token, basta envolverlos en middleware('auth:sanctum')).

// --- Primeras pruebas que hice en Productos antes de estandarizar las rutas REST ---
// Route::get('/productos/listar', [ProductoController::class, 'listar'])->name('productos.listar');
// Route::get('/productos/crear', [ProductoController::class, 'create'])->name('productos.crear');
// Route::post('/productos', [ProductoController::class, 'store'])->name('productos.guardar');
// Route::get('/productos/{id?}', [ProductoController::class, 'vistaEdicion'])->name('productos.editar');
// Route::put('/productos/{id?}', [ProductoController::class, 'actualizar'])->name('productos.actualizar');
// Route::get('/productos/{id?}', [ProductoController::class, 'vistaMostrar'])->name('productos.mostrar');
// Route::delete('/productos/{id?}', [ProductoController::class, 'borrar'])->name('productos.borrar');

// 1. Roles
Route::get('/roles', [RolController::class, 'index'])->name('roles.index');
Route::get('/roles/{id}', [RolController::class, 'show'])->whereNumber('id')->name('roles.show');
Route::post('/roles', [RolController::class, 'store'])->name('roles.store');
Route::put('/roles/{id}', [RolController::class, 'update'])->whereNumber('id')->name('roles.update');
Route::delete('/roles/{id}', [RolController::class, 'destroy'])->whereNumber('id')->name('roles.destroy');

// 2. Administradores
Route::get('/administradores', [AdministradorController::class, 'index'])->name('administradores.index');
Route::get('/administradores/{id}', [AdministradorController::class, 'show'])->whereNumber('id')->name('administradores.show');
Route::post('/administradores', [AdministradorController::class, 'store'])->name('administradores.store');
Route::put('/administradores/{id}', [AdministradorController::class, 'update'])->whereNumber('id')->name('administradores.update');
Route::delete('/administradores/{id}', [AdministradorController::class, 'destroy'])->whereNumber('id')->name('administradores.destroy');

// 3. Categorías
Route::get('/categorias', [CategoriaController::class, 'index'])->name('categorias.index');
Route::get('/categorias/{id}', [CategoriaController::class, 'show'])->whereNumber('id')->name('categorias.show');
Route::post('/categorias', [CategoriaController::class, 'store'])->name('categorias.store');
Route::put('/categorias/{id}', [CategoriaController::class, 'update'])->whereNumber('id')->name('categorias.update');
Route::delete('/categorias/{id}', [CategoriaController::class, 'destroy'])->whereNumber('id')->name('categorias.destroy');

// 4. Presentaciones
Route::get('/presentaciones', [PresentacionController::class, 'index'])->name('presentaciones.index');
Route::get('/presentaciones/{id}', [PresentacionController::class, 'show'])->whereNumber('id')->name('presentaciones.show');
Route::post('/presentaciones', [PresentacionController::class, 'store'])->name('presentaciones.store');
Route::put('/presentaciones/{id}', [PresentacionController::class, 'update'])->whereNumber('id')->name('presentaciones.update');
Route::delete('/presentaciones/{id}', [PresentacionController::class, 'destroy'])->whereNumber('id')->name('presentaciones.destroy');

// 5. Marcas
Route::get('/marcas', [MarcaController::class, 'index'])->name('marcas.index');
Route::get('/marcas/{id}', [MarcaController::class, 'show'])->whereNumber('id')->name('marcas.show');
Route::post('/marcas', [MarcaController::class, 'store'])->name('marcas.store');
Route::put('/marcas/{id}', [MarcaController::class, 'update'])->whereNumber('id')->name('marcas.update');
Route::delete('/marcas/{id}', [MarcaController::class, 'destroy'])->whereNumber('id')->name('marcas.destroy');

// 6. Productos
Route::get('/productos', [ProductoController::class, 'index'])->name('productos.index');
Route::get('/productos/{id}', [ProductoController::class, 'show'])->whereNumber('id')->name('productos.show');
Route::post('/productos', [ProductoController::class, 'store'])->name('productos.store');
Route::put('/productos/{id}', [ProductoController::class, 'update'])->whereNumber('id')->name('productos.update');
Route::delete('/productos/{id}', [ProductoController::class, 'destroy'])->whereNumber('id')->name('productos.destroy');

// 7. Clientes
Route::get('/clientes', [ClienteController::class, 'index'])->name('clientes.index');
Route::get('/clientes/{id}', [ClienteController::class, 'show'])->whereNumber('id')->name('clientes.show');
Route::post('/clientes', [ClienteController::class, 'store'])->name('clientes.store');
Route::put('/clientes/{id}', [ClienteController::class, 'update'])->whereNumber('id')->name('clientes.update');
Route::delete('/clientes/{id}', [ClienteController::class, 'destroy'])->whereNumber('id')->name('clientes.destroy');

// 8. Direcciones
Route::get('/direcciones', [DireccionController::class, 'index'])->name('direcciones.index');
Route::get('/direcciones/{id}', [DireccionController::class, 'show'])->whereNumber('id')->name('direcciones.show');
Route::post('/direcciones', [DireccionController::class, 'store'])->name('direcciones.store');
Route::put('/direcciones/{id}', [DireccionController::class, 'update'])->whereNumber('id')->name('direcciones.update');
Route::delete('/direcciones/{id}', [DireccionController::class, 'destroy'])->whereNumber('id')->name('direcciones.destroy');

// 9. Métodos de Pago
Route::get('/metodos_pago', [MetodoPagoController::class, 'index'])->name('metodos_pago.index');
Route::get('/metodos_pago/{id}', [MetodoPagoController::class, 'show'])->whereNumber('id')->name('metodos_pago.show');
Route::post('/metodos_pago', [MetodoPagoController::class, 'store'])->name('metodos_pago.store');
Route::put('/metodos_pago/{id}', [MetodoPagoController::class, 'update'])->whereNumber('id')->name('metodos_pago.update');
Route::delete('/metodos_pago/{id}', [MetodoPagoController::class, 'destroy'])->whereNumber('id')->name('metodos_pago.destroy');

// 10. Pedidos
Route::get('/pedidos', [PedidoController::class, 'index'])->name('pedidos.index');
Route::get('/pedidos/{id}', [PedidoController::class, 'show'])->whereNumber('id')->name('pedidos.show');
Route::post('/pedidos', [PedidoController::class, 'store'])->name('pedidos.store');
Route::put('/pedidos/{id}', [PedidoController::class, 'update'])->whereNumber('id')->name('pedidos.update');
Route::delete('/pedidos/{id}', [PedidoController::class, 'destroy'])->whereNumber('id')->name('pedidos.destroy');

// 11. Productos por Pedido
Route::get('/productos_pedido', [ProductoPedidoController::class, 'index'])->name('productos_pedido.index');
Route::get('/productos_pedido/{id}', [ProductoPedidoController::class, 'show'])->whereNumber('id')->name('productos_pedido.show');
Route::post('/productos_pedido', [ProductoPedidoController::class, 'store'])->name('productos_pedido.store');
Route::put('/productos_pedido/{id}', [ProductoPedidoController::class, 'update'])->whereNumber('id')->name('productos_pedido.update');
Route::delete('/productos_pedido/{id}', [ProductoPedidoController::class, 'destroy'])->whereNumber('id')->name('productos_pedido.destroy');

// 12. Repartidores
Route::get('/repartidores', [RepartidorController::class, 'index'])->name('repartidores.index');
Route::get('/repartidores/{id}', [RepartidorController::class, 'show'])->whereNumber('id')->name('repartidores.show');
Route::post('/repartidores', [RepartidorController::class, 'store'])->name('repartidores.store');
Route::put('/repartidores/{id}', [RepartidorController::class, 'update'])->whereNumber('id')->name('repartidores.update');
Route::delete('/repartidores/{id}', [RepartidorController::class, 'destroy'])->whereNumber('id')->name('repartidores.destroy');

// 13. Entregas
Route::get('/entregas', [EntregaController::class, 'index'])->name('entregas.index');
Route::get('/entregas/{id}', [EntregaController::class, 'show'])->whereNumber('id')->name('entregas.show');
Route::post('/entregas', [EntregaController::class, 'store'])->name('entregas.store');
Route::put('/entregas/{id}', [EntregaController::class, 'update'])->whereNumber('id')->name('entregas.update');
Route::delete('/entregas/{id}', [EntregaController::class, 'destroy'])->whereNumber('id')->name('entregas.destroy');

// Usuarios adicionales (tabla users)
Route::get('/users', [UserController::class, 'index'])->name('users.index');
Route::get('/users/{id}', [UserController::class, 'show'])->whereNumber('id')->name('users.show');
Route::post('/users', [UserController::class, 'store'])->name('users.store');
Route::put('/users/{id}', [UserController::class, 'update'])->whereNumber('id')->name('users.update');
Route::delete('/users/{id}', [UserController::class, 'destroy'])->whereNumber('id')->name('users.destroy');

// Servicios externos consumidos en JSON (Geolocalización, Pronóstico del Clima y Tipo de Cambio)
Route::get('/ubicacion', [GeolocalizacionController::class, 'obtenerUbicacion'])->name('api.ubicacion');
Route::get('/pronostico', [PronosticoController::class, 'obtenerPronostico'])->name('api.pronostico');
Route::get('/dolar', [DolarController::class, 'obtenerDolar'])->name('api.dolar');
