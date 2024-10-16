<?php

namespace App\Http\Controllers\Api;

use Exception;
use App\Models\User;
use App\Models\Escola;
use App\Models\Planosacao;
use Illuminate\Http\Request;
use App\Models\Coordenadores;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class EscolaController extends Controller
{

    private function normalizeString($string)
    {
        // Remover acentos e caracteres especiais
        $normalizedString = iconv('UTF-8', 'ASCII//TRANSLIT', $string);
        // Transformar em maiúsculas e remover espaços extras
        return strtoupper(trim($normalizedString));
    }

    /**
     * @OA\Get(
     *     path="/api/view-escola",
     *     summary="Obtém a lista de escolas ordenadas em ordem asc e por SIGEAM.",
     *     @OA\Response(
     *         response=200,
     *         description="Lista de escolas retornada com sucesso.",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="status",
     *                 type="boolean",
     *                 example=true
     *             ),
     *         )
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     *
     * Obtém a lista de escolas ordenadas por SIGEAM.
     *
     * @return JsonResponse
     */
    public function escolas(): JsonResponse
    {
        $escolas = Escola::orderBy('sigeam', 'ASC')->get();
        return response()->json([
            'status' => true,
            'escolas' => $escolas,
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/escolas/view-registros/cde-cdre",
     *     summary="Visualiza planos de ação com nome das escolas e coordenadorias e seus coordenadores (Admin e Secretaria)",
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
     *         description="Lista de planos de ação com detalhes das escolas e coordenadores.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="status",
     *                 type="boolean",
     *                 example=true
     *             )
     *         )
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     *
     * Visualiza planos de ação com detalhes das escolas e coordenadores.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $escolas = Escola::orderBy('sigeam', 'ASC')->get();

            // Extrair todos os valores de sigeam, distrito e municipio das escolas
            $sigeams = $escolas->pluck('sigeam');
            $distritos = $escolas->pluck('distrito');
            $municipios = $escolas->pluck('municipio')->map(fn($item) => $this->normalizeString($item))->unique();

            $planosacoes = Planosacao::whereIn('sigeam', $sigeams)->paginate(150);

            // Buscar todos os coordenadores que têm uma coordenadoria correspondente
            $coordenadoresPorDistrito = Coordenadores::whereIn('coordenadoria', $distritos)->get();

            // Buscar todos os coordenadores que têm um municipio correspondente
            $coordenadoresPorMunicipio = Coordenadores::all()->map(function ($coordenador) {
                $coordenador->municipio_normalizado = $this->normalizeString($coordenador->municipio);
                return $coordenador;
            });

            // Mapear os dados de escolas para associar com planos de ação e coordenadores
            $escolasComDados = $escolas->map(function ($escola) use ($planosacoes, $coordenadoresPorDistrito, $coordenadoresPorMunicipio) {
                $planoacoes = $planosacoes->where('sigeam', $escola->sigeam)
                    ->map(function ($plano) {
                        return [ 'tipo_acao' => $plano->tipo_acao ];
                    })->unique('tipo_acao'); // Remove duplicatas de tipo_acao

                // Se o distrito não for "-", encontrar o coordenador correspondente ao distrito
                if ($escola->distrito !== '-') {
                    $coordenador = $coordenadoresPorDistrito->where('coordenadoria', $escola->distrito)->first();
                } else {
                    // Se o distrito for "-", encontrar o coordenador correspondente ao municipio
                    $municipioNormalizado = $this->normalizeString($escola->municipio);
                    $coordenador = $coordenadoresPorMunicipio->where('municipio', $municipioNormalizado)->first();
                }

                return [
                    'sigeam' => $escola->sigeam,
                    'escola' => $escola->escola,
                    'municipio' => $escola->municipio,
                    'distrito' => $escola->distrito,
                    'planoacao' => $planoacoes,
                    'coordenador' => $coordenador ? [
                        'gestao' => $coordenador->gestao,
                        'coordenadoria' => $coordenador->coordenadoria,
                        'coordenador' => $coordenador->coordenador,
                        'assessor' => $coordenador->assessor,
                    ] : null
                ];
            })->filter(function ($escola) {
                return $escola['planoacao']->isNotEmpty();
            });

            return response()->json([
                'status' => true,
                'escolas' => $escolasComDados
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => "Erro ao obter dados: " . $e->getMessage()
            ], 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/view-registros/user/{id}",
     *     summary="Visualiza planos de ação por ID do usuário, coordenador ou assessor.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *             example=4
     *         ),
     *         description="ID do usuário para buscar planos de ação."
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de planos de ação com detalhes das escolas e coordenadores.",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="status",
     *                 type="boolean",
     *                 example=true
     *             ),
     *             @OA\Property(
     *                 property="escolas",
     *                 type="object",
     *                 additionalProperties=@OA\Property(
     *                     type="object",
     *                     @OA\Property(property="sigeam", type="integer"),
     *                     @OA\Property(property="escola", type="string"),
     *                     @OA\Property(property="municipio", type="string"),
     *                     @OA\Property(property="distrito", type="string"),
     *                     @OA\Property(
     *                         property="planoacao",
     *                         type="object",
     *                         additionalProperties=@OA\Property(
     *                             type="object",
     *                             @OA\Property(property="tipo_acao", type="string")
     *                         )
     *                     ),
     *                     @OA\Property(
     *                         property="coordenador",
     *                         type="object",
     *                         @OA\Property(property="gestao", type="string"),
     *                         @OA\Property(property="coordenadoria", type="string"),
     *                         @OA\Property(property="coordenador", type="string"),
     *                         @OA\Property(property="assessor", type="string"),
     *                         @OA\Property(property="userid", type="integer"),
     *                         @OA\Property(property="assessorid", type="integer")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     *
     * Visualiza planos de ação por ID do usuário, coordenador ou assessor.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function indexID(Request $request, $userId): JsonResponse
    {
        try {
            // Obter todas as escolas
            $escolas = Escola::orderBy('sigeam', 'ASC')->get();

            // Extrair todos os valores de sigeam, distrito e municipio das escolas
            $sigeams = $escolas->pluck('sigeam');
            $distritos = $escolas->pluck('distrito');
            $municipios = $escolas->pluck('municipio')->map(fn($item) => $this->normalizeString($item))->unique();

            // Obter todos os planos de ação para os sigeams das escolas
            $planosacoes = Planosacao::whereIn('sigeam', $sigeams)->get();

            // Buscar todos os coordenadores que têm uma coordenadoria ou municipio correspondente
            $coordenadoresPorDistrito = Coordenadores::whereIn('coordenadoria', $distritos)->get();
            $coordenadoresPorMunicipio = Coordenadores::all()->map(function ($coordenador) {
                $coordenador->municipio_normalizado = $this->normalizeString($coordenador->municipio);
                return $coordenador;
            });

            // Mapear os dados de escolas para associar com planos de ação e coordenadores
            $escolasComDados = $escolas->map(function ($escola) use ($planosacoes, $coordenadoresPorDistrito, $coordenadoresPorMunicipio, $userId) {
                // Filtrar planos de ação para a escola atual
                $planoacoes = $planosacoes->where('sigeam', $escola->sigeam)
                    ->map(function ($plano) {
                        return [ 'tipo_acao' => $plano->tipo_acao ];
                    })->unique('tipo_acao'); // Remove duplicatas de tipo_acao

                // Verificar se o distrito não é "-"
                $coordenador = null;
                if ( $escola->distrito !== '-') {
                    $coordenador = $coordenadoresPorDistrito -> where('coordenadoria', $escola->distrito)->first();
                } else {
                    // Se o distrito for "-", encontrar o coordenador correspondente ao municipio
                    $municipioNormalizado = $this->normalizeString($escola->municipio);
                    $coordenador = $coordenadoresPorMunicipio -> where('municipio', $municipioNormalizado)->first();
                }

                // Verificar se o coordenador ou assessor é relevante para o usuário
                if ($coordenador && ($coordenador->user_id == $userId || $coordenador->assessor_id == $userId)) {
                    // Verificar se existem planos de ação não vazios
                    if ($planoacoes->isNotEmpty()) {
                        return [
                            'sigeam' => $escola->sigeam,
                            'escola' => $escola->escola,
                            'municipio' => $escola->municipio,
                            'distrito' => $escola->distrito,
                            'planoacao' => $planoacoes,
                            'coordenador' => [
                                'gestao' => $coordenador->gestao,
                                'coordenadoria' => $coordenador->coordenadoria,
                                'coordenador' => $coordenador->coordenador,
                                'assessor' => $coordenador->assessor,
                                'userid' => $coordenador->user_id,
                                'assessorid' => $coordenador->assessor_id,
                            ]
                        ];
                    }
                }

                return null;
            })->filter(); // Remove qualquer valor null

            return response()->json([
                'status' => true,
                'escolas' => $escolasComDados
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => "Erro ao obter dados: " . $e->getMessage()
            ], 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/view-escola/acao/{tipo_acao}/sigeam/{sigeam}",
     *     summary="Retorna detalhes dos planos de ação filtrados por tipo de ação e SIGEAM.",
     *     @OA\Parameter(
     *         name="tipo_acao",
     *         in="path",
     *         required=true,
     *         @OA\Schema(
     *             type="string",
     *             example="tipo_acao"
     *         ),
     *         description="O tipo de ação para filtrar os registros."
     *     ),
     *     @OA\Parameter(
     *         name="sigeam",
     *         in="path",
     *         required=true,
     *         @OA\Schema(
     *             type="string",
     *             example="4"
     *         ),
     *         description="O código SIGEAM para filtrar os registros."
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalhes dos planos de ação encontrados com sucesso.",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="status",
     *                 type="boolean",
     *                 example=true
     *             ),
     *             @OA\Property(
     *                 property="acao",
     *                 type="object"
     *             )
     *         )
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     *
     * Método que retorna detalhes dos planos de ação por tipo de ação e SIGEAM.
     *
     */
    public function showAcaoEscola($tipo_acao, $sigeam)
    {
        try {
            // Buscar registros na tabela 'planosacao' onde 'tipo_acao' e 'sigeam' sejam iguais aos parâmetros
            $registros = Planosacao::where('tipo_acao', $tipo_acao)
                                   ->where('sigeam', $sigeam)
                                   ->paginate(10);

            // Verificar se algum registro foi encontrado
            // if ($registros->isEmpty()) {
            //     return response()->json([
            //         'status' => false,
            //         'message' => 'Nenhum objeto encontrado com os parâmetros fornecidos!',
            //     ], 404);
            // }

            // Retornar os dados encontrados
            return response()->json([
                'status' => true,
                'acao' => $registros,
            ], 200);

        } catch (\Exception $e) {
            // Em caso de erro, retornar uma mensagem de erro
            return response()->json([
                'status' => false,
                'message' => "Erro ao obter dados: " . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/view-registros/{sigeam}",
     *     summary="Visualiza planos de ação e detalhes das escolas por SIGEAM -> perfil escola",
     *     @OA\Parameter(
     *         name="sigeam",
     *         in="path",
     *         required=true,
     *         @OA\Schema(
     *             type="string",
     *             example="4"
     *         ),
     *         description="Código SIGEAM da escola para filtragem dos registros."
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de escolas com planos de ação"
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     *
     * Método de vizualiação de planos de ação por id -> perfil escolar
     *
     * @param Request $request
     * @param string $sigeam
     * @return JsonResponse
     */
    public function showEscolasId(Request $request, $sigeam): JsonResponse
    {
        try {

            $escolas = Escola::where('sigeam', $sigeam) // Buscar todas as escolas e ordenar por sigeam
                             ->orderBy('sigeam', 'DESC')
                             ->get();

            // Extrair todos os valores de sigeam, distrito e municipio das escolas
            $sigeams = $escolas->pluck('sigeam');
            $distritos = $escolas->pluck('distrito');
            $municipios = $escolas->pluck('municipio')->map(fn($item) => $this->normalizeString($item))->unique();

            $planosacoes = Planosacao::whereIn('sigeam', $sigeams)->get(); // Buscar todos os planos de ação que têm um sigeam correspondente

            $coordenadoresPorDistrito = Coordenadores::whereIn('coordenadoria', $distritos)->get();  // Buscar todos os coordenadores que têm uma coordenadoria correspondente

            $coordenadoresPorMunicipio = Coordenadores::all()->map(function ($coordenador) {
                $coordenador->municipio_normalizado = $this->normalizeString($coordenador->municipio);
                return $coordenador;
            }); // Buscar todos os coordenadores que têm um municipio correspondente

            // Mapear os dados de escolas para associar com planos de ação e coordenadores
            $escolasComDados = $escolas->map(function ($escola) use ($planosacoes, $coordenadoresPorDistrito, $coordenadoresPorMunicipio) {
                $planoacoes = $planosacoes->where('sigeam', $escola->sigeam) // Encontrar os planos de ação correspondentes ao sigeam da escola
                    ->map(function ($plano) {
                        return [
                            'tipo_acao' => $plano->tipo_acao
                        ];
                    })->unique('tipo_acao'); // Remove duplicatas de tipo_acao

                // Se o distrito não for "-", encontrar o coordenador correspondente ao distrito
                if ($escola->distrito !== '-') {
                    $coordenador = $coordenadoresPorDistrito->where('coordenadoria', $escola->distrito)->first();
                } else {
                    // Se o distrito for "-", encontrar o coordenador correspondente ao municipio
                    $municipioNormalizado = $this->normalizeString($escola->municipio);
                    $coordenador = $coordenadoresPorMunicipio->where('municipio', $municipioNormalizado)->first();
                }

                return [
                    'sigeam' => $escola->sigeam,
                    'escola' => $escola->escola,
                    'municipio' => $escola->municipio,
                    'distrito' => $escola->distrito,
                    'planoacao' => $planoacoes,
                    'coordenador' => $coordenador ? [
                        'gestao' => $coordenador->gestao,
                        'coordenadoria' => $coordenador->coordenadoria,
                        'coordenador' => $coordenador->coordenador,
                        'assessor' => $coordenador->assessor,
                    ] : null
                ];
            })->filter(function ($escola) {
                return $escola['planoacao']->isNotEmpty(); // Filtra para manter apenas escolas que têm planos de ação
            });

            return response()->json([
                'status' => true,
                'escolas' => $escolasComDados
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => "Erro ao obter dados: " . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/export-dados/user/{id}",
     *     summary="Export csv Planos de Ações com os registros coordenador e assessor",
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         required=true,
     *         @OA\Schema(
     *             type="string",
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Sucesso!"
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     *
     * Export\baixar csv Planos de Ações com os registros coordenador e assessor
     *
     */
    public function ExportDataUserEscola(Request $request, $user)
    {
        try {
            // Obter todas as escolas
            $escolas = Escola::orderBy('sigeam', 'ASC')->get();

            // Extrair todos os valores de sigeam, distrito e municipio das escolas
            $sigeams = $escolas->pluck('sigeam');
            $distritos = $escolas->pluck('distrito');
            $municipios = $escolas->pluck('municipio')->map(fn($item) => strtoupper(trim($item)))->unique();

            // Obter todos os planos de ação para os sigeams das escolas
            $planosacoes = Planosacao::whereIn('sigeam', $sigeams)->get();

            // Buscar todos os coordenadores que têm uma coordenadoria ou municipio correspondente
            $coordenadoresPorDistrito = Coordenadores::whereIn('coordenadoria', $distritos)->get();
            $coordenadoresPorMunicipio = Coordenadores::whereIn('municipio', $municipios)->get();

            // Mapear os dados de escolas para associar com planos de ação e coordenadores
            $registros = $escolas->map(function ($escola) use ($planosacoes, $coordenadoresPorDistrito, $coordenadoresPorMunicipio, $user) {
                // Filtrar planos de ação para a escola atual
                $planoacoes = $planosacoes->where('sigeam', $escola->sigeam);

                // Verificar se o distrito não é "-"
                $coordenador = null;
                if ($escola->distrito !== '-') {
                    $coordenador = $coordenadoresPorDistrito->where('coordenadoria', $escola->distrito)->first();
                } else {
                    // Se o distrito for "-", encontrar o coordenador correspondente ao municipio
                    $municipioNormalizado = strtoupper(trim($escola->municipio));
                    $coordenador = $coordenadoresPorMunicipio->where('municipio', $municipioNormalizado)->first();
                }

                // Verificar se o coordenador ou assessor é relevante para o usuário
                if ($coordenador && ($coordenador->user_id == $user || $coordenador->assessor_id == $user)) {
                    // Verificar se existem planos de ação não vazios
                    if ($planoacoes->isNotEmpty()) {
                        return $planoacoes->map(function ($registro) {
                            return [
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
                        });
                    }
                }

                return null;
            })->filter()->flatten(1); // Remove qualquer valor null e achata o array de arrays

            if ($registros->isEmpty()) { // Verifica se há dados a serem exportados
                return response()->json([
                    'message' => "Sem dados para exportar!"
                ]);
            }

            $csvFileName = 'planoacao.csv'; // Define o nome do CSV
            $csvFilePath = storage_path('app/' . $csvFileName); // Constrói o caminho completo para onde o arquivo CSV será armazenado

            $csvHeader = [ // Cabeçalho do CSV
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
                'Relato de Execucao da Tarefa',
                'Pontos Problematicos',
                'Acao Futura',
                'Responsavel',
                'Prazo',
            ];

            // Abre o arquivo para escrita
            $file = fopen($csvFilePath, 'w');
            fputcsv($file, $csvHeader, ';');

            foreach ($registros as $registro) { // Adiciona os dados no CSV
                fputcsv($file, $registro, ';');
            }

            fclose($file); // Fecha o arquivo após a escrita

            return response()->download($csvFilePath, $csvFileName, [ // Retorna o arquivo CSV como resposta
                'Content-Type' => 'text/csv',
            ])->deleteFileAfterSend(true);

        } catch (Exception $e) {
            return response()->json([
                'message' => "Erro ao acessar a rota",
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/export-dados/escola/{sigeam}",
     *     summary="Export csv Planos de Ações com os registros por escola",
     *     @OA\Parameter(
     *         name="sigeam",
     *         in="path",
     *         required=true,
     *         @OA\Schema(
     *             type="string",
     *             example="4"
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Sucesso!"
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     *
     * Export csv Planos de Ações com os registros por escola
     *
     */
    public function ExportDataEscola(Request $request, $sigeam)
    {
        try{

            $escolas = Escola::where('sigeam', $sigeam) // Buscar todas as escolas e ordenar por sigeam
                             ->orderBy('sigeam', 'DESC')
                             ->get();

            // Extrair todos os valores de sigeam, distrito e municipio das escolas
            $sigeams = $escolas->pluck('sigeam');
            $distritos = $escolas->pluck('distrito');
            $municipios = $escolas->pluck('municipio')->map(fn($item) => strtoupper(trim($item)))->unique();

            $registros = Planosacao::whereIn('sigeam', $sigeams)->get();// recupera todos os registros da tabela

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
                'Responsavel',
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

}
