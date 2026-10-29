<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $table = 'clientes';

    protected $fillable = [
        'nombres',
        'apellidos',
        'correo',
        'contraseña',
        'telefono',
        'imagen',
        'estado'
    ];

    protected $hidden = [
        'contraseña',
    ];

    public function direcciones()
    {
        return $this->hasMany(Direccion::class);
    }

    public function pedidos()
    {
        return $this->hasMany(Pedido::class);
    }
}