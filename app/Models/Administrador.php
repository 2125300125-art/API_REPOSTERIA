<?php

namespace App\Models;

// use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

// class Administrador extends Model
class Administrador extends Authenticatable
{
    // Agregué HasApiTokens de Sanctum para poder generar tokens de autenticación en la API REST.
    // use Notifiable;
    use HasFactory, Notifiable, HasApiTokens;

    protected $table = 'administradores';

    protected $fillable = [
        'nombre',
        'apellidos',
        'correo',
        'usuario',
        'contraseña',
        'imagen',
        'rol_id',
        'activo',
    ];

    protected $hidden = [
        'contraseña',
    ];

    public function getAuthPassword()
    {
        return $this->contraseña;
    }

    public function rol()
    {
        return $this->belongsTo(Rol::class);
    }
}
