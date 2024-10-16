<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;


// Visualizar usuários
Route::get('/users', [UserController::class, 'index']);
//Cadastro de usuários
Route::post('/users',[UserController::class, 'store']);
//Atualizar dados do usuário
Route::put('/users/{user}',[UserController::class, 'update']);
//vizualizar usuários por id
Route::get('/users/{user}', [UserController::class, 'show']);
//Apaga um usuário
Route::delete('/users/{user}',[UserController::class, 'destroy']);