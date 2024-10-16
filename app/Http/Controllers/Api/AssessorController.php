<?php

namespace App\Http\Controllers\Api;

use Exception;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Coordenadores;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class AssessorController extends Controller
{

    /**
     * @OA\Get(
     *     path="/api/assessor/{assessor_id}",
     *     summary="Visualiza os coordenadores com base no assessor_id.",
     *     @OA\Parameter(
     *         name="assessor_id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="ID do assessor para buscar os coordenadores."
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Retorna a lista de vizualição de assessores"
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     *
     * Método de vizualição de assessores
     *
     */
    public function index(Request $request, $assessor_id): JsonResponse
    {
        try {
            // Encontra os assessores pelo assessor_id
            $assessores = Coordenadores::where('assessor_id', $assessor_id)->get();

            // Verifica se encontrou algum assessor
            if ($assessores->isEmpty()) {
                return response()->json([
                    'message' => 'Nenhum assessores encontrado para o usuário especificado.'
                ], 404);
            }

            // Retorna os dados dos coordenadores
            return response()->json($assessores, 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao buscar coordenadores.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/assessor/{assessor_id}",
     *     summary="Associa um usuário a uma coordenadoria (CDE) com base no ID do assessor.",
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
     *         description="Usuário associado a uma coordenadoria com sucesso."
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     *     )
     *
     * Método que traz o id do assessor para ele ser associado a uma cde.
     *
     * @param Request $request
     * @param User $user
     * @return JsonResponse
     */
    public function update(Request $request, User $user): JsonResponse
    {
        DB::beginTransaction();

        try {

            if ($user->assessor == 1) { // Verifica se 'assessor' é igual a 1
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
                            $municipioCoord->update(['assessor_id' => $user->id]); // Atualiza o município com o ID do usuário
                        } else {
                            return response()->json([
                                'message' => "Município não encontrado.",
                            ], 404);
                        }
                    }

                    // Atualiza a coordenadoria
                    $coord->update(['assessor_id' => $user->id]);

                } else {
                    return response()->json([
                        'message' => "Coordenadoria não encontrada.",
                    ], 404);
                }
            } else {
                return response()->json([
                    'message' => "Usuário não é um assessor ou não tem permissão de assessor",
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

}
