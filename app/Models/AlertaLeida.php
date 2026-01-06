<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlertaLeida extends Model
{
    use HasFactory;

    protected $table = 'alertas_users';

    protected $fillable = [
        'alerta_id',
        'user_id',
        'leida_en',
    ];

    public function alerta()
    {
        return $this->belongsTo(Alerta::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
