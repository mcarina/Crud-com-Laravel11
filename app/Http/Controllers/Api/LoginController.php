<?php

namespace App\Http\Controllers\Api;

use \App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\RedirectResponse;

/**
 * @OA\Info(
 *     title="API",
 *     version="1.0",
 *     description="Siges/swagger.json"
 * ),
 *     @OA\SecurityScheme(
 *         securityScheme="bearerAuth",
 *         type="http",
 *         scheme="bearer",
 *         bearerFormat="JWT",
 *         in="header",
 *         name="Authorization",
 *         description="Adicione o token JWT no cabeçalho da requisição. Exemplo: 'Authorization: Bearer {token}'"
 *     )
 * )
 */

class LoginController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/login",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="nadielesouza@educacao.am.gov.br"),
     *             @OA\Property(property="password", type="string", format="password", example="12345678")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Login bem-sucedido"
     *     ),
     * )
     *
     * Método de login, cria uma hash para o usuário.
     */

    public function login(Request $request): JsonResponse
    {
        // Validar o email e a senha fornecidos
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            // Recuperar os dados do usuário autenticado
            $user = Auth::user();

            // Criar um token de autenticação para o usuário
            $token = $user->createToken('api-token')->plainTextToken;

            // Retornar uma resposta de sucesso com o token
            return response()->json([
                'status' => true,
                'token' => $token,
            ], 201);
        } else {
            // Autenticação falhou, retornar uma resposta de erro
            return response()->json([
                'status' => false,
                'message' => 'Email ou senha incorreta'
            ], 404);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/logout",
     *     @OA\Parameter(
     *         name="user_id",
     *         in="path",
     *         description="ID do usuário",
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *             example=1
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Logout bem-sucedido"
     *     )
     * )
     *
     * Método logout, destroi o token de acesso do usuário.
     *
     * @param  \App\Models\User  $user
     */
    public function logout(User $user): JsonResponse
    {
        try {
            // Deletar todos os tokens de autenticação do usuário
            $user->tokens()->delete();

            // Retornar uma resposta de sucesso
            return response()->json([
                'status' => true,
                'message' => "Deslogado!",
            ], 201);

        } catch (Exception $e) {
            // Em caso de erro ao fazer logout, retornar uma resposta de erro
            return response()->json([
                'status' => false,
                'message' => "Erro ao sair!"
            ], 404);
        }
    }

    // ==============  NÃO FUNCIONA AUTENTICAÇÃO COM GOOGLE ==================== \\

    /**
     * Login com conta google.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirectToGoogle(): RedirectResponse
    {
        try {
            return Socialite::driver('google')->stateless()->redirect();
        } catch (Exception $e) {
            // Em caso de erro ao redirecionar
            return response()->json([
                'message' => "Erro ao redirecionar usuário!",
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Retorno da autenticação via Google.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleGoogleCallback(): JsonResponse
    {
        try {
            // Obtém as informações do usuário do Google
            $user = Socialite::driver('google')->stateless()->user();

            // Registra ou faz login do usuário
            $this->_registerOrLoginUser($user);

            // Cria um log de ação
            Log::create([
                'usuario' => $user->name,
                'acao' => "LOGIN através da conta GOOGLE"
            ]);

            return response()->json([
                'message' => "Usuário autenticado com sucesso pelo Google!"
            ], 200);

        } catch(Exception $e){
            return response()->json([
                'message' => "Erro ao redirecionar usuário!"
            ],400);
        }
    }


    protected function _registerOrLoginUser($data)
    {

        $user = User::where('email', '=', $data->email)->first();
        if (!$user) {
            $user = new User();
            $user->name = $data->name;
            $user->email = $data->email;
            $user->provider_id = $data->id;
            $user->avatar = $data->avatar;
            $user->save();
        }

        Auth::login($user);

    }

}
