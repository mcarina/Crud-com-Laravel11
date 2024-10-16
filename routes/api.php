<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\CoordController;
use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Api\EscolaController;
use App\Http\Controllers\Api\AssessorController;
use App\Http\Controllers\Api\PlanoAcaoController;

//login -> rota publica
Route::post('/login', [LoginController::class, 'login']) -> name('login');

//Login Google NÃO FUNCIONA
// Route::get('google/callback', [LoginController::class, 'handleGoogleCallback']);
// Route::get('google/redirect', [LoginController::class, 'redirectToGoogle']) -> name('google');

//Rotas restritas
Route::group(['middleware' => 'auth:sanctum'], function () {

    //visualizar as rotinas do usuário
    Route::get('/info', [UserController::class, 'showInfo']);
    //vizualizar usuários por id
    Route::get('/users/{user}', [UserController::class, 'show']);
    //remove o token de acesso do usuário
    Route::post('/logout/{user}', [LoginController::class, 'logout']);
    // vizualizar dados das escolas
    Route::get('/view-escola', [EscolaController::class, 'escolas']);


    Route::middleware(['RestrictAdmin'])->group(function () {
        /**
         user controller
         */

        //Cadastro de usuários
        Route::post('/users',[UserController::class, 'store']);
        // Visualizar usuários
        Route::get('/users', [UserController::class, 'index']);
        //Atualizar dados do usuário
        Route::put('/users/{user}',[UserController::class, 'update']);
        //Apaga um usuário
        Route::delete('/users/{user}',[UserController::class, 'destroy']);
        //importação de arquivo csv -> criar usuários subindo um csv
        // Route::post('/import-users', [UserController::class, 'import']);s

        /**
         coordenador controller
         */
        // Update coordenadores -> associar um coordenador a uma cde
        Route::put('/coord/{user}', [CoordController::class, 'updateCoord']);
        //delete coordenador
        // Route::delete('/coord/{id}', [CoordController::class, 'destroy']);
        //export do csv dos coordenadores e assessores
        Route::get('/export-coord', [CoordController::class, 'Export']);
        //Import -> criar Coordenadores subindo um csv
        Route::post('/import-coord', [CoordController::class, 'importCoord']);

        /**
         Plano de ação de controller
         */
        //baixar\export csv com todos os dos planos de ação com dados
        Route::get('/export-dados', [PlanoAcaoController::class, 'ExportData']);

    });


    Route::middleware(['RestrictPermissions'])->group(function () {
        /**
         Plano de ação controller
         */

        //baixar\export csv dos planos de ação, csv vazio
        Route::get('/export', [PlanoAcaoController::class, 'Export']);
        //Atualização dos planos de ações via csv
        Route::post('/update-dados', [PlanoAcaoController::class, 'UpdateData']);
        //importação do csv dos planos de ação -> subir planos de ação via csv
        Route::post('/import-planosacao', [PlanoAcaoController::class, 'Upload']);
        //cria planos de ação por vez, rota para formulario
        Route::post('/create-plano-acao', [PlanoAcaoController::class, 'storeForm']);

        // Update -> atualiza planos de ação por id
        Route::put('/view-registros/{id}', [PlanoAcaoController::class, 'update']);
        // retorna registro dos planos de ação
        Route::get('/view-registros', [PlanoAcaoController::class, 'indexRegistros']);
        // retorna registro dos planos de ação por ano
        Route::get('/view-registros/{ano?}', [PlanoAcaoController::class, 'indexRegistroAno']);
        // APAGAR registros -> planos de ação
        Route::delete('/view-registros/{id}', [PlanoAcaoController::class, 'destroy']);

        /**
         escola controller
         */

        // export csv planos de acao com dados do banco
        Route::get('/export-dados/user/{user}', [EscolaController::class, 'ExportData']);
        // baixa o csv de escolas por sigeam
        Route::get('/export-dados/escola/{sigeam}', [EscolaController::class, 'ExportDataEscola']);
        // visualizar planos de ação por id do user, coordenador ou assessor
        Route::get('/view-registros/user/{user}', [EscolaController::class, 'indexID']);
        //vizualizar planos de ação perfil escola
        Route::get('/view-registros/{sigeam}', [EscolaController::class, 'showEscolasId']);
        // vizualizar planos de ação por tipo de ação e por sigeam
        Route::get('/view-escola/acao/{tipo_acao}/sigeam/{sigeam}', [EscolaController::class, 'showAcaoEscola']);

        /**
         coordenador controller
         */
        //visualizar coordenadores registrados, cde e assessores
        Route::get('/coord', [CoordController::class, 'indexCoord']);
        //visualizar coordenadores por id - 'meus assessores' no front-end
        Route::get('/coord/{user}', [CoordController::class, 'indexCoordID']);

        /**
         Assessores controller
         */
        //update -> associar um assessores a uma cde
        Route::put('/assessor/{user}', [AssessorController::class, 'update']);
        // visualizar assessores por id
        Route::get('/assessor/{user}', [AssessorController::class, 'index']);

    });

    //vizualizar planos de ação com nome das escolas e coordenadorias e seu coordenadores
    Route::get('/escolas/view-registros/cde-cdre', [EscolaController::class, 'index'])->middleware('RestrictAdminSecret');

});
