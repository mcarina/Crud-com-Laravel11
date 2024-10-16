<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Http\Controllers\Controller;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\UserRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/users",
     *     summary="lista de usuários(Admin)",
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
     *         description="Lista de usuários retornada com sucesso"
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     *
     * Método que retorna uma lista de usuários ordenados por ID em ordem crescente, com paginação.
     */
    public function index(): JsonResponse
    {
        // Sem paginação
        // $users = User::orderBy('id', 'ASC')->get();

        $users = User::orderBy('id', 'ASC')-> paginate(150);
        // Usuários ordenados por ID em ordem decrescente

        return response()->json([
            'status' => true,
            'users' => $users,
        ], 200);
    }

    /**
     *     @OA\Get(
     *     path="/api/users/{id}",
     *     summary="Retorna os detalhes de um usuário por ID.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID do usuário para recuperar os detalhes",
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *             example=1
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalhes do usuário retornados com sucesso"
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     * Método que retorna detalhes do usuário, por ID.
     *
     * @param \App\Models\User $user
     */
    public function show(User $user): JsonResponse
    {
        return response()->json([
            'status' => true,
            'user' => $user,
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/users",
     *     summary="Cria um novo usuário (Admin)",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", example="johndoe@example.com"),
     *             @OA\Property(property="password", type="string", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Usuário cadastrado com sucesso"
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     *
     * Método que permite criar novos usuários.
     *
     * @param \App\Http\Requests\UserRequest $request
     * @return JsonResponse
     */
    public function store(UserRequest $request): JsonResponse
    {
        DB::beginTransaction();// Iniciando transação

        try {
            User::create([ // Criando um novo usuário
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password), // Criptografando a senha
            ]);

            DB::commit(); // Confirmando a transação

            return response()->json([
                'message' => "Usuário cadastrado com sucesso!",
            ], 201);

        } catch (Exception $e) {
            DB::rollback(); // Falha na operação, rollback na transação

            return response()->json([
                'status' => false,
                'message' => "Erro ao cadastrar usuário, verifique o email",
            ], 400);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/users/{id}",
     *     summary="Atualiza os dados de um usuário existente(Admin)",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID do usuário a ser atualizado",
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *             example=1
     *         )
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="admin", type="boolean", example=false),
     *             @OA\Property(property="assessor", type="boolean", example=false),
     *             @OA\Property(property="p_escola", type="boolean", example=false),
     *             @OA\Property(property="coordenador", type="boolean", example=false),
     *             @OA\Property(property="coord_nig", type="boolean", example=false),
     *             @OA\Property(property="secretaria", type="boolean", example=false),
     *             @OA\Property(property="escola", type="string", example="Escola ABC", description="Necessário se p_escola é verdadeiro"),
     *             @OA\Property(property="password", type="string", example="newpassword123", description="Senha a ser atualizada, opcional")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Campos atualizados com sucesso"
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     *
     * Método que atualiza os dados de um usuário.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\User $user
     * @return JsonResponse
     */
    public function update(Request $request, User $user): JsonResponse
    {
        DB::beginTransaction(); // Iniciando transação

        try {
            // Validação dos campos
            $rules = [
                'name' => 'required|string|max:255',
                'admin' => 'nullable|boolean',
                'assessor' => 'nullable|boolean',
                'p_escola' => 'nullable|boolean',
                'coordenador' => 'nullable|boolean',
                'coord_nig' => 'nullable|boolean',
                'secretaria' => 'nullable|boolean',
                'password' => 'nullable|string|min:6',
            ];

            // Adicionar a validação condicional para 'escola'
            if ($request->p_escola == 1) {
                $rules['escola'] = 'required|string|max:255';
            }

            // Validar a request
            $validatedData = $request->validate($rules);

            // Verificando se apenas um dos campos tem valor 1
            $roles = [
                'admin' => $request->admin,
                'assessor' => $request->assessor,
                'p_escola' => $request->p_escola,
                'coordenador' => $request->coordenador,
                'coord_nig' => $request->coord_nig,
                'secretaria' => $request->secretaria,
            ];

            $count = array_sum($roles);

            if ($count > 1) {
                return response()->json([
                    'status' => false,
                    'message' => "Apenas um campo pode ter o valor 1.",
                ], 400);
            }

            // Atualizando os dados do usuário
            $user->update([
                'name' => $request->name,
                'admin' => $request->admin ?? 0,
                'assessor' => $request->assessor ?? 0,
                'p_escola' => $request->p_escola ?? 0,
                'coordenador' => $request->coordenador ?? 0,
                'coord_nig' => $request->coord_nig ?? 0,
                'secretaria' => $request->secretaria ?? 0,
                'escola' => $request->p_escola == 1 ? $request->escola : null,
                'password' => $request->filled('password') ? bcrypt($request->password) : $user->password, // Criptografando a senha
            ]);

            DB::commit();  // Confirmando a transação

            return response()->json([
                'message' => "Campos atualizados!",
            ], 201);

        } catch (Exception $e) {
            DB::rollback(); // Falha na operação, rollback na transação

            return response()->json([
                'status' => false,
                'message' => "Erro ao atualizar campos: " . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/users/{id}",
     *     summary="Deleta um usuário(Admin)",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID do usuário a ser deletado",
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *             example=1
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
     * Método que deleta um usuário.
     *
     * @param \App\Models\User $user
     * @return JsonResponse
     */
    public function destroy(User $user): JsonResponse
    {
        DB::beginTransaction();  // Iniciando transação

        try {
            $user->delete(); // Deletando o usuário

            DB::commit(); // Confirmando a transação

            return response()->json([
                'usuario' => $user,
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
