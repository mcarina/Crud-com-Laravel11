<?php

namespace App\Http\Controllers\Api;

use Exception;
use App\Models\Planosacao;
use App\Jobs\ImportCsvJob;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;


class PlanoAcaoController extends Controller
{

    /**
     * @OA\Get(
     *     path="/api/view-registros",
     *     summary="Retorna uma lista de planos de ação/registros paginados.",
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Número da página para a paginação",
     *         required=false,
     *         @OA\Schema(
     *             type="integer",
     *             example=1
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de registros retornada com sucesso"
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     *
     * Método que visualiza registros realizados.
     *
     * @return JsonResponse
     */
    public function indexRegistros(): JsonResponse
    {
        try {
            $registros = Planosacao::orderBy('id', 'ASC')-> paginate(150);

            return response()->json([
                'status' => true,
                'registros' => $registros,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => "Erro ao obter dados: " . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/view-registros/{ano}",
     *     summary="Retorna uma lista de planos de ação/registros paginados.",
     *     description="Retorna uma lista de registros filtrados por ano, se o parâmetro for fornecido. Caso contrário, retorna todos os registros.",
     *     @OA\Parameter(
     *         name="ano",
     *         in="path",
     *         description="Ano dos registros a serem filtrados",
     *         required=false,
     *         @OA\Schema(
     *             type="integer",
     *             example=2024
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Número da página para a paginação",
     *         required=false,
     *         @OA\Schema(
     *             type="integer",
     *             example=1
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de registros retornada com sucesso",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="status",
     *                 type="boolean",
     *                 example=true
     *             ),
     *             @OA\Property(
     *                 property="registros",
     *                 type="object",
     *                 description="Dados paginados dos registros",
     *                 @OA\Property(
     *                     property="current_page",
     *                     type="integer",
     *                     example=1
     *                 ),
     *                 @OA\Property(
     *                     property="total",
     *                     type="integer",
     *                     example=150
     *                 )
     *             )
     *         )
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     *
     * Método que visualiza registros realizados, podendo filtrar por ano.
     *
     * @param int|null $ano
     * @return JsonResponse
     */
    public function indexRegistroAno(?int $ano = null): JsonResponse
    {
        try {
            // Verifica se o parâmetro ano foi passado
            if ($ano) {
                // Filtra os registros pelo ano informado
                $registros = Planosacao::where('ano', $ano)->orderBy('id', 'ASC')->paginate(150);
            } else {
                // Se o parâmetro não for passado, retorna todos os registros
                $registros = Planosacao::orderBy('id', 'ASC')->paginate(150);
            }

            return response()->json([
                'status' => true,
                'registros' => $registros,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => "Erro ao obter dados: " . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/create-plano-acao",
     *     summary="Criar um novo plano de ação",
     *     description="Este endpoint permite a criação de um novo registro de plano de ação.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"tipo_acao", "causa_correlacionda", "sigeam", "indicador", "acao", "tarefa", "responsavel", "prev_inicio", "prev_fim", "status"},
     *             @OA\Property(property="tipo_acao", type="string", example="Tipo de Ação Exemplo"),
     *             @OA\Property(property="causa_correlacionda", type="string", example="Causa Correlacionada Exemplo"),
     *             @OA\Property(property="sigeam", type="string", example="Sigeam Exemplo"),
     *             @OA\Property(property="indicador", type="string", example="Indicador Exemplo"),
     *             @OA\Property(property="acao", type="string", example="Ação Exemplo"),
     *             @OA\Property(property="tarefa", type="string", example="Tarefa Exemplo"),
     *             @OA\Property(property="responsavel", type="string", example="Responsável Exemplo"),
     *             @OA\Property(property="prev_inicio", type="string", format="date", example="2024-10-14"),
     *             @OA\Property(property="prev_fim", type="string", format="date", example="2024-10-20"),
     *             @OA\Property(property="real_inicio", type="string", format="date", example="2024-10-15"),
     *             @OA\Property(property="real_fim", type="string", format="date", example="2024-10-25"),
     *             @OA\Property(property="status", type="string", example="Em Andamento"),
     *             @OA\Property(property="prazo_final", type="string", format="date", example="2024-10-30")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Lista de registros retornada com sucesso"
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     * )
     */

    public function storeForm(Request $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            // Validação dos dados
            $validatedData = $request->validate([
                'tipo_acao' => 'required|string',
                'causa_correlacionda' => 'required|string',
                'sigeam' => 'required|string',
                'indicador' => 'required|string',
                'acao' => 'required|string',
                'tarefa' => 'required|string',
                'responsavel' => 'required|string',
                'prev_inicio' => 'required|date',
                'prev_fim' => 'required|date',
                'real_inicio' => 'nullable|date',
                'real_fim' => 'nullable|date',
                'status' => 'required|string',
                'prazo_final' => 'nullable|date', // Torne este campo opcional
            ]);

            // Criação do registro
            $plano = new Planosacao();
            $plano->tipo_acao = $validatedData['tipo_acao'];
            $plano->causa_correlacionda = $validatedData['causa_correlacionda'];
            $plano->sigeam = $validatedData['sigeam'];
            $plano->indicador = $validatedData['indicador'];
            $plano->acao = $validatedData['acao'];
            $plano->tarefa = $validatedData['tarefa'];
            $plano->responsavel = $validatedData['responsavel'];
            $plano->prev_inicio = $validatedData['prev_inicio'];
            $plano->prev_fim = $validatedData['prev_fim'];
            $plano->real_inicio = $validatedData['real_inicio'] ?? null; // Usar null se não houver valor
            $plano->real_fim = $validatedData['real_fim'] ?? null; // Usar null se não houver valor
            $plano->status = $validatedData['status'];
            $plano->prazo_final = $validatedData['prazo_final'] ?? null; // Usar null se não houver valor
            $plano->created_at = now(); // Se necessário
            $plano->ano = now()->year; // Se necessário
            $plano->save();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Registro cadastrado com sucesso!',
                'data' => $plano,
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => false,
                'message' => "Erro ao cadastrar no formulário: " . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/view-registros/{id}",
     *     summary="Atualiza os dados de um plano de ação existente, sem csv.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID do plano de ação a ser atualizado",
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *             example=1
     *         )
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(property="real_inicio", type="string", format="date", example="2024-01-01"),
     *                 @OA\Property(property="real_fim", type="string", format="date", example="2024-01-31"),
     *                 @OA\Property(property="status", type="string", example="PENDENTE"),
     *                 @OA\Property(property="relato_exec_taref", type="string", example="Relato do progresso da tarefa."),
     *                 @OA\Property(property="pontos_probl", type="string", example="Problemas encontrados."),
     *                 @OA\Property(property="acao_fut", type="string", example="Ação futura planejada."),
     *                 @OA\Property(property="responsavel_atras", type="string", example="Responsável pelo atraso."),
     *                 @OA\Property(property="prazo_final", type="string", format="date", example="2024-02-28"),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Plano de ação atualizado com sucesso"
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     *
     * Método que atualiza os dados de um plano de ação.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            // Encontrar o plano de ação pelo ID
            $plano = Planosacao::findOrFail($id);

            // Validar os dados do request
            $validated = $request->validate([
                'real_inicio' => 'nullable|date',
                'real_fim' => 'nullable|date',
                'status' => 'nullable|string',
                'relato_exec_taref' => 'nullable|string',
                'pontos_probl' => 'nullable|string',
                'acao_fut' => 'nullable|string',
                'responsavel_atras' => 'nullable|string',
                'prazo_final' => 'nullable|date',
            ]);

            // Verificar se o status é CANCELADO
            if (isset($validated['status']) && $validated['status'] === 'CANCELADO') {
                $plano->status = 'CANCELADO';

            } else {
                // Verificar se o campo 'relato_exec_taref' está preenchido e o status não é 'CONCLUIDO'
                if (!empty($validated['relato_exec_taref']) && (!isset($validated['status']) || $validated['status'] !== 'CONCLUIDO')) {
                    return response()->json([
                        'message' => 'A ação precisa estar CONCLUIDO.',
                    ], 400);
                }

                // Verificar se os campos relacionados ao status 'ATRASADO' estão preenchidos
                // if (!empty($validated['pontos_probl']) && (!isset($validated['status']) || $validated['status'] !== 'ATRASADO')) {
                //     return response()->json([
                //         'message' => 'A ação precisa estar CONCLUIDO.',
                //     ], 400);
                // }

                // Atualizar os campos normalmente
                $plano->fill($validated);
            }

            // Atualizar o status se não for 'CANCELADO'
            if (!isset($validated['status']) || $validated['status'] !== 'CANCELADO') {
                $plano->status = $validated['status'] ?? $plano->status;
            }

            // Salvar as alterações
            $plano->save();

            return response()->json([
                'message' => 'Plano de ação atualizado com sucesso',
                'plano' => $plano,
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Erro ao atualizar o plano de ação',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/import-planosacao",
     *     summary="Importa um arquivo CSV para criar planos de ação.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(
     *                     property="file",
     *                     type="string",
     *                     format="binary",
     *                     description="Arquivo CSV contendo os planos de ação a serem importados."
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Sucesso!"
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     *
     Método que importação de um arquivo CSV para criar planos de ação.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function Upload(Request $request): JsonResponse
    {
        try {
            // Valida o arquivo
            $request->validate([
                'file' => 'required|mimes:csv,txt|max:8192',
            ],[
                'file.required' => "Selecione um arquivo!",
                'file.mimes' => "Escolha um arquivo do tipo csv!",
                'file.max' => "Tamanho máximo permitido, de arquivo permitido, de :max Mb",
            ]);

            // Armazena o arquivo no storage
            $filePath = $request->file('file')->store('uploads');

            // Despacha o job para processamento em background
            ImportCsvJob::dispatch($filePath);

            return response()->json([
                'message' => 'O arquivo foi enviado e será processado em segundo plano.',
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/export",
     *     summary="Método que exporta o csv de planos de ação (vazio).",
     *     @OA\Response(
     *         response=200,
     *         description="Arquivo CSV com os registros exportados",
     *         @OA\MediaType(
     *             mediaType="text/csv",
     *             @OA\Schema(
     *                 type="string",
     *                 format="binary"
     *             )
     *         )
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     *
     * Método que exporta o csv de planos de ação (vazio).
     *
     * @param Request $request
     * @return Response
     */
    public function Export(Request $request)
    {
        try {
            $columns = [
                'tipo_acao' => 'Tipo Acao',
                'causa_correlacionda' => 'Causa Correlacionada',
                'sigeam' => 'Sigeam',
                'indicador' => 'Indicador',
                'acao' => 'Acao',
                'tarefa' => 'Tarefa',
                'responsavel' => 'Responsavel',
                'prev_inicio' => 'Previsao de Inicio',
                'prev_fim' => 'Previsao de Fim',
                'real_inicio' => 'Real Inicio',
                'real_fim' => 'Real Fim',
                'status' => 'Status',
                'relato_exec_taref' => 'Relato de Execucao da Tarefa',
                'pontos_probl' => 'Pontos Problematicos',
                'acao_fut' => 'Acao Futura',
                'responsavel_atras' => 'Responsavel_atras',
                'prazo_final' => 'Prazo',
            ];

            // Verifica se há dados, se não, retorna apenas o cabeçalho
            $data = Planosacao::select(array_keys($columns))->get();

            $csvContent = implode(';', array_values($columns)) . "\n";

            if (!$data->isEmpty()) {
                foreach ($data as $row) {
                    $csvContent .= implode(';', $row->toArray()) . "\n";
                }
            }

            // Define o cabeçalho para download do CSV
            return response($csvContent)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="planosacao.csv"');

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }


    /**
     * @OA\Get(
     *     path="/api/export-dados",
     *     summary="Exporta todos os planos de ação para um CSV (Admin)",
     *     @OA\Response(
     *         response=200,
     *         description="Arquivo CSV com os registros exportados",
     *         @OA\MediaType(
     *             mediaType="text/csv",
     *             @OA\Schema(
     *                 type="string",
     *                 format="binary"
     *             )
     *         )
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     *
     * Export\baixar csv Planos de Ações com os registros
     *
     * @param Request $request
     * @return Response
     */
    public function ExportData(Request $request)
    {
        try{

            $registros = Planosacao::all(); // recupera todos os registros da tabela

            if($registros->isEmpty()){ // verifica se a dados a serem exportados
                return response()->json([
                    'message' => "Sem dados para exportar!"
                ]);
            }

            $csvFileName = 'planoacao.csv'; // define o nome do csv
            $csvFilePath = storage_path('app/' . $csvFileName); //constrói o caminho completo para onde o arquivo CSV será armazenado

            $csvHeader = [ //cabeçalho do csv
                'id',
                'Tipo Acao',
                'Causa Correlacionada',
                'Sigeam',
                'Indicador',
                'Acao',
                'Tarefa',
                'Responsavel',
                'Previsao de Inicio',
                'Previsao de Fim',
                'Real Inicio',
                'Real Fim',
                'Status',
                'Relato de Excecucao da Tarefa',
                'Pontos Problematicos',
                'Acao Futura',
                'Responsavel_atras',
                'Prazo',
            ];

            // Abre o arquivo para escrita
            $file = fopen($csvFilePath, 'w');
            fputcsv($file, $csvHeader, ';');

            foreach ($registros as $registro) { //adiciona os dados no csv
                $csvData = [
                    $registro->id,
                    $registro->tipo_acao,
                    $registro->causa_correlacionda,
                    $registro->sigeam,
                    $registro->indicador,
                    $registro->acao,
                    $registro->tarefa,
                    $registro->responsavel,
                    $registro->prev_inicio,
                    $registro->prev_fim,
                    $registro->real_inicio,
                    $registro->real_fim,
                    $registro->status,
                    $registro->relato_exec_taref,
                    $registro->pontos_probl,
                    $registro->acao_fut,
                    $registro->responsavel_atras,
                    $registro->prazo,
                ];

                fputcsv($file, $csvData, ';');
            }


            fclose($file);  // Fecha o arquivo após a escrita

            return response()->download($csvFilePath, $csvFileName, [ // Retorna o arquivo CSV como resposta
                'Content-Type' => 'text/csv',
            ])->deleteFileAfterSend(true);

        }catch(Exception $e){
            return response()->json([
                'message' => "Erro ao acessar a rota",
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/update-dados",
     *     summary="Atualiza registros dos planos de ação usando um arquivo CSV.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(
     *                     property="file",
     *                     type="string",
     *                     format="binary",
     *                     description="Arquivo CSV com os dados para atualizar os registros."
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Registros atualizados com sucesso.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Registros atualizados com sucesso!")
     *         )
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     *
     * Atualização dos planos de ações via csv
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function UpdateData(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'file' => 'required|mimes:csv,txt|max:8192',
            ], [
                'file.required' => "Selecione um arquivo!",
                'file.mimes' => "Escolha um arquivo do tipo csv!",
                'file.max' => "Tamanho máximo permitido de :max MB",
            ]);

            $datafile = array_map('str_getcsv', file($request->file('file')));
            array_shift($datafile); // Remove o cabeçalho

            $headers = [
                'id',
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

            $headerCount = count($headers);

            foreach ($datafile as $row) {
                $values = explode(';', $row[0]); // Converte a linha em array

                // Verifica se o número de colunas está correto
                if (count($values) != $headerCount) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Número incorreto de colunas no arquivo CSV.',
                    ], 400);
                }

                $record = []; // Montar o registro com base nos cabeçalhos
                foreach ($headers as $key => $header) {
                    if (isset($values[$key])) {
                        if (in_array($header, ['prev_inicio', 'prev_fim', 'real_inicio', 'real_fim', 'prazo_final'])) {
                            $dateValue = trim($values[$key]);
                            if (!empty($dateValue)) {
                                $date = \DateTime::createFromFormat('d/m/Y', $dateValue);
                                $record[$header] = $date !== false ? $date->format('Y-m-d') : null;
                            } else {
                                $record[$header] = null;
                            }
                        } else {
                            $record[$header] = $values[$key];
                        }
                    } else {
                        $record[$header] = null;
                    }
                }

                if (isset($record['id'])) { // Verifica se a coluna 'id' está presente
                    Planosacao::updateOrCreate(
                        ['id' => $record['id']], // Chave para encontrar o registro existente
                        $record // Dados para atualizar
                    );
                }
            }

            return response()->json([
                'message' => 'Registros atualizados com sucesso!',
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Erro ao atualizar os registros.',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/view-registros/{id}",
     *     summary="deleta planos de ação",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="deleta planos de ação",
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Usuário deletado com sucesso"
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     *
     * Método que deleta planos de ação.
     *
     * @param \App\Models\Planosacao $user
     * @return JsonResponse
     */
    public function destroy(Planosacao $id): JsonResponse
    {
        try{
            DB::beginTransaction();  // Iniciando transação

            $id->delete(); // Deletando o plano de ação

            DB::commit(); // Confirmando a transação

        return response()->json([
            'registros' => $id,
            'message' => "Plano de ação apagado!",
        ], 201);

        }catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => "Falha ao apagar Plano de Ação",
            ], 400);

        }

    }

}
