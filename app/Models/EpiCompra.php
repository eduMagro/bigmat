<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EpiCompra extends Model
{
    protected $table = 'epi_compras';

    protected $fillable = [
        'user_id',
        'estado',
        'comprada_en',
        'ticket_path',
    ];

    protected $casts = [
        'comprada_en' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(EpiCompraItem::class, 'compra_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

