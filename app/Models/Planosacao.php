<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Planosacao extends Model
{
    use HasFactory;
    public $timestamps = true;

    protected $table = 'planosacao';

    protected $fillable = [
        'tipo_acao',
        'causa_correlacionda',
        'sigeam',
        'indicador',
        'acao',
        'tarefa',
        'responsavel',
        'prev_inicio',
        'prev_fim',
        'real_inicio',
        'real_fim',
        'status',
        'relato_exec_taref',
        'pontos_probl',
        'acao_fut',
        'responsavel_atras',
        'prazo_final',
    ];

    public function getStatusAttribute()
    {

        if ($this->attributes['status'] === 'CANCELADO') {
            return 'CANCELADO';
        }

        $now = Carbon::now();
        $prevInicio = Carbon::parse($this->attributes['prev_inicio']);
        $prevFim = Carbon::parse($this->attributes['prev_fim']);
        $realFim = $this->attributes['real_fim'];
        $prazoFinal = $this->attributes['prazo_final'];


        // Se real fim for preenchido, ele marcará como concluído
        if (!empty($realFim)) {
            return 'CONCLUIDO';
        }

        // Se prazo final for preenchido, porem a data for menor que o dia atual será "atrasado", ou se ele for maior sera 'em andamento'
        if (!empty($prazoFinal)) {
            $prazoFinalDate = Carbon::parse($prazoFinal);

            if ($prazoFinalDate->lessThan($now)) {
                return 'ATRASADO';
            } elseif ($prazoFinalDate->greaterThan($now)) {
                return 'EM ANDAMENTO';
            }
        }

        // Determine status based on prev_inicio and prev_fim
        if ($prevFim->lessThan($now)) {
            return 'ATRASADO';
        } elseif ($prevInicio->greaterThan($now)) {
            return 'INICIANDO';
        } else {
            return 'EM ANDAMENTO';
        }
    }

}
