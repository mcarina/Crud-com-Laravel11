<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coordenadores extends Model
{
    use HasFactory;

    protected $table = '_coordenadores';

    protected $fillable = [
        'gestao',
        'coordenadoria', 
        'municipio',
        'coordenador', 
        'assessor',
        'user_id',
        'assessor_id',
];

public function user()
{
    return $this->belongsTo(User::class);
}
}


