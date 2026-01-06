<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrabajadorFicticio extends Model
{
    use HasFactory;

    protected $table = 'trabajadores_ficticios';

    protected $fillable = [
        'nombre',
    ];
}
