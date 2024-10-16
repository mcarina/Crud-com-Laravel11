<?php

namespace App\Http\Controllers\Api;

use Exception;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Coordenadores;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class CoordController extends Controller
{

    /**
     * @OA\Get(
     *     path="/api/coord",
     *     summary="Retornar uma lista de coordenadores.",
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
     *         description="Retornar uma lista de coordenadores."
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     *
     * Método que permite retornar uma lista de coordenadores.
     *
     */
    public function indexCoord(): JsonResponse
    {
        try{

            $coord = Coordenadores::orderBy('id', 'ASC')->paginate(100);

            return response()->json([
                'coordenadores' => $coord,
            ], 200);

        }catch(Exception $e){
            return response()->json([
                'status' => false,
                'message' => "Erro ao listar coordenadores: " . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/coord/{user_id}",
     *     summary="Retorna detalhes do coordenador por ID.",
     *     description="Este endpoint permite buscar coordenadores associados a um determinado ID de usuário.",
     *     @OA\Parameter(
     *         name="user_id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(
     *             type="integer"
     *         ),
     *         description="ID do usuário para buscar coordenadores."
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalhes dos coordenadores encontrados."
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     *
     * Método que retorna detalhes do coordenador, por ID.
     *
     *
     * @param Request $request
     * @param int $user_id
     * @return JsonResponse
     */
    public function indexCoordID(Request $request, $user_id): JsonResponse
    {
        try {
            // Encontra os coordenadores pelo user_id
            $coordenadores = Coordenadores::where('user_id', $user_id)->get();

            // Verifica se encontrou algum coordenador
            if ($coordenadores->isEmpty()) {
                return response()->json([
                    'message' => 'Nenhum coordenador encontrado para o usuário especificado.'
                ], 404);
            }

            // Retorna os dados dos coordenadores
            return response()->json($coordenadores, 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao buscar coordenadores.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/coord/{id}",
     *     summary="Associa um usuário a uma coordenadoria (CDE) com base no ID do usuario.",
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="ID do usuário a ser associado à coordenadoria."
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(property="coordenadoria", type="string", example="Coordenadoria Exemplo"),
     *                 @OA\Property(property="municipio", type="string", example="O Município é obrigatório apenas se a coordenadoria for regional"),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Usuário associado a uma CDE com sucesso."
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     *
     * Método que traz o id do coordenador para ele ser associado a uma cde.
     *
     * @param Request $request
     * @param User $user
     * @return JsonResponse
     */
    public function updateCoord(Request $request, User $user): JsonResponse
    {
        DB::beginTransaction();

        try {

            if ($user->coordenador == 1 || $user->coord_nig == 1) { // Verifica se 'coordenador' ou 'coord_nig' é igual a 1
                $coordenadoria = $request->input('coordenadoria'); // Pega a coordenadoria a partir da requisição
                $municipio = $request->input('municipio'); // Pega o município a partir da requisição

                $coord = Coordenadores::where('coordenadoria', $coordenadoria)->first(); // Encontra a coordenadoria específica

                if ($coord) {
                    // Se a coordenadoria for do tipo "REGIONAL", o município é obrigatório
                    if ($coord->coordenadoria === 'REGIONAL') {
                        if (!$municipio) {
                            return response()->json([
                                'message' => "O município é obrigatório para coordenadorias do tipo 'REGIONAL'.",
                            ], 400);
                        }

                        $municipioCoord = Coordenadores::where('municipio', $municipio)->first();

                        if ($municipioCoord) {
                            $municipioCoord->update(['user_id' => $user->id]); // Atualiza o município com o ID do usuário
                        } else {
                            return response()->json([
                                'message' => "Município não encontrado.",
                            ], 404);
                        }
                    }else {
                        // Se não for 'REGIONAL', apenas a coordenadoria principal é atualizada
                        $coord->update(['user_id' => $user->id]);
                    }

                } else {
                    return response()->json([
                        'message' => "Coordenadoria não encontrada.",
                    ], 404);
                }
            } else {
                return response()->json([
                    'message' => "Usuário não é um coordenador ou não tem permissão de coordenador",
                ], 400);
            }

            DB::commit();  // Confirmando a transação

            return response()->json([
                'message' => "Usuário associado a uma CDE!",
            ], 200);

        } catch (Exception $e) {

            DB::rollback();

            return response()->json([
                'message' => "Erro ao acessar a rota!",
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/import-coord",
     *     summary="Importa novos coordenadores a partir de um arquivo CSV.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 type="object",
     *                 required={"file"},
     *                 @OA\Property(
     *                     property="file",
     *                     type="string",
     *                     format="binary",
     *                     description="Arquivo CSV contendo os dados dos coordenadores."
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Registros importados com sucesso."
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     *
     * Importa novos coordenadores a partir de um arquivo CSV.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function importCoord(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'file' => 'required|mimes:csv,txt|max:8192',
            ],[
                'file.required' => "Selecione um arquivo!",
                'file.mimes' => "Escolha um arquivo do tipo csv!",
                'file.max' => "Tamanho máximo permitido, de arquivo permitido, de :max Mb",
            ]);

            $datafile = array_map('str_getcsv', file($request->file('file')));
            array_shift($datafile); //remove a primeira linha

            $records = [];
            $numberRegisteredRecords = 0;

            $headers = [
                'gestao',
                'coordenadoria',
                'municipio',
                'coordenador',
                'assessor',
            ]; // Criar um array com as colunas no banco de dados

            foreach ($datafile as $row) {
                $values = explode(';', $row[0]); // Converte a linha em array

                $record = [];  // Montar o registro com base nos cabeçalhos
                foreach ($headers as $key => $header) {
                    if (isset($values[$key])) {
                        $record[$header] = strtoupper(trim($values[$key]));
                    }
                }

                if (!empty($record)) {
                    $records[] = $record;
                    $numberRegisteredRecords++;
                }
            }

            Coordenadores::insert($records);

            return response()->json([
                'message' => 'Registros criados!',
                'Total de Registros' => $numberRegisteredRecords
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/export-coord",
     *     summary="Export csv coordenadores.",
     *     @OA\Response(
     *         response=200,
     *         description="Export csv coordenadores.",
     *         @OA\MediaType(
     *             mediaType="application/csv",
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
     * Exporta todos os dados dos coordenadores para um arquivo CSV.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function Export(Request $request)
    {
        try{

            $registros = Coordenadores::all(); // recupera todos os registros da tabela

            if($registros->isEmpty()){ // verifica se a dados a serem exportados
                return response()->json([
                    'message' => "Sem dados para exportar!"
                ]);
            }

            $csvFileName = 'coordenadores.csv'; // define o nome do csv
            $csvFilePath = storage_path('app/' . $csvFileName); //constrói o caminho completo para onde o arquivo CSV será armazenado

            $csvHeader = [ //cabeçalho do csv
                'gestao',
                'coordenadoria',
                'municipio',
                'coordenador',
                'assessor',
            ];

            // Abre o arquivo para escrita
            $file = fopen($csvFilePath, 'w');
            fputcsv($file, $csvHeader, ';');

            // foreach ($registros as $registro) { //adiciona os dados no csv
            //     $csvData = [
            //         $registro->gestao,
            //         $registro->coordenadoria,
            //         $registro->municipio,
            //         $registro->coordenador,
            //         $registro->assessor,
            //     ];

            //     fputcsv($file, $csvData, ';');
            // }


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
     * Método delete coordenadores
     *
     */
    public function destroy(Coordenadores $id): JsonResponse
    {
        DB::beginTransaction();  // Iniciando transação

        try {
            $id->delete(); // Deletando o usuário

            DB::commit(); // Confirmando a transação

            return response()->json([
                'message' => "Usuário apagado!",
            ], 201);

        } catch (Exception $e) {

            DB::rollback(); // Falha na operação, rollback na transação

            return response()->json([
                'status' => false,
                'message' => "Falha ao apagar usuário",
            ], 400);
        }
    }


}
