<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EpiCompraItem extends Model
{
    protected $table = 'epi_compra_items';

    protected $fillable = [
        'compra_id',
        'epi_id',
        'cantidad',
        'precio_unitario',
    ];

    protected $casts = [
        'precio_unitario' => 'decimal:2',
    ];

    public function compra()
    {
        return $this->belongsTo(EpiCompra::class, 'compra_id');
    }

    public function epi()
    {
        return $this->belongsTo(Epi::class, 'epi_id');
    }
}

