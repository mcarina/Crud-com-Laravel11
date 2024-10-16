<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Escola extends Model
{
    use HasFactory;

    // Define o nome da tabela associada ao modelo
    protected $table = 'escolas';

    // Define as colunas que podem ser preenchidas em massa
    protected $fillable = [
        'sigeam',
        'escola',
        'municipio',
        'distrito',
    ];
}
