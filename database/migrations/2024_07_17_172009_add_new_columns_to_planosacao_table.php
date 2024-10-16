<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('planosacao', function (Blueprint $table) {
            $table->string('relato_exec_taref')->nullable();
            $table->string('pontos_probl')->nullable();
            $table->string('acao_fut')->nullable();
            $table->string('responsavel_atras')->nullable();
            $table->string('prazo')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('planosacao', function (Blueprint $table) {
            //
        });
    }
};
