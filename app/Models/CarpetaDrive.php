<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CarpetaDrive extends Model
{
    protected $fillable = [
        'cliente_id',
        'parent_id', // nuevo
        'tipo',
        'drive_folder_id',
        'nombre',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(CarpetaDrive::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(CarpetaDrive::class, 'parent_id');
    }
    
}
