<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use League\Csv\Reader;
use League\Csv\Statement;
use App\Models\Planosacao;

class ImportCsvJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $filePath;

    /**
     * Create a new job instance.
     */
    public function __construct($filePath)
    {
        $this->filePath = $filePath;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $csv = Reader::createFromPath(storage_path('app/' . $this->filePath), 'r');
            Log::info('Tentando abrir o arquivo: ' . storage_path('app/' . $this->filePath));

            $csv->setDelimiter(';');
            $csv->setHeaderOffset(0); // Define que a primeira linha é o cabeçalho

            $records = $csv->getRecords();

            $csvColumnToDbColumn = [
                'Tipo Acao' => 'tipo_acao',
                'Causa Correlacionada' => 'causa_correlacionda',
                'Sigeam' => 'sigeam',
                'Indicador' => 'indicador',
                'Acao' => 'acao',
                'Tarefa' => 'tarefa',
                'Responsavel' => 'responsavel',
                'Previsao de Inicio' => 'prev_inicio',
                'Previsao de Fim' => 'prev_fim',
                'Real Inicio' => 'real_inicio',
                'Real Fim' => 'real_fim',
                'Status' => 'status',
                'Relato de Execucao da Tarefa' => 'relato_exec_taref',
                'Pontos Problematicos' => 'pontos_probl',
                'Acao Futura' => 'acao_fut',
                'Responsavel_atras' => 'responsavel_atras',
                'Prazo' => 'prazo_final',
            ];

            $dataToInsert = [];
            $batchSize = 100; // Definindo o tamanho do lote

            foreach ($records as $index => $record) {
                Log::info('Registro lido: ' . json_encode($record));

                // Valida se a linha contém dados, ignorando registros vazios
                if (!empty(array_filter($record))) {
                    $data = [];

                    // Mapeamento das colunas do CSV para as colunas do banco
                    foreach ($csvColumnToDbColumn as $csvColumn => $dbColumn) {
                        $data[$dbColumn] = $this->formatField($record[$csvColumn] ?? null, $dbColumn);
                    }

                    $data['created_at'] = now();
                    $data['ano'] = now()->year;

                    $dataToInsert[] = $data;

                    // Se o tamanho do lote for alcançado, insira os dados no banco
                    if (count($dataToInsert) >= $batchSize) {
                        Log::info('Inserindo ' . count($dataToInsert) . ' registros no banco de dados');
                        Planosacao::insert($dataToInsert);
                        Log::info('Inserção concluída');
                        $dataToInsert = []; // Limpa o array para o próximo lote
                    }
                }
            }

            // Insere quaisquer registros restantes que não foram inseridos ainda
            if (!empty($dataToInsert)) {
                Log::info('Inserindo ' . count($dataToInsert) . ' registros restantes no banco de dados');
                Planosacao::insert($dataToInsert);
                Log::info('Inserção concluída');
            }

        } catch (\Exception $e) {
            Log::error('Erro ao salvar no banco de dados: ' . $e->getMessage());
            $this->fail($e); // Falha o job em caso de exceção
        }
    }


    /**
     * Formata o campo dependendo do tipo (data ou texto).
     */
    private function formatField($value, $header)
    {
        // Tratar campos de data
        if (in_array($header, ['prev_inicio', 'prev_fim', 'real_inicio', 'real_fim', 'prazo_final'])) {
            return $this->formatDate($value);
        }

        // Retorna o valor padrão
        return $value;
    }

    /**
     * Formata a data para o formato 'Y-m-d'.
     */
    private function formatDate($date)
    {
        if (!empty($date)) {
            $dateObj = \DateTime::createFromFormat('d/m/Y', $date);
            return $dateObj ? $dateObj->format('Y-m-d') : null;
        }
        return null;
    }
}
