<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Categoria extends Model
{
    use HasFactory;

    protected $table = 'categorias';

    public $timestamps = false;

    protected $fillable = ['nombre'];

    public function users()
    {
        return $this->hasMany(User::class, 'categoria_id');
    }
}
