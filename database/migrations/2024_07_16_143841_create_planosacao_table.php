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
        Schema::create('planosacao', function (Blueprint $table) {
            $table->id();
            $table->text('tipo_acao');
            $table->text('causa_correlacionda');
            $table->string('sigeam');
            $table->text('indicador');
            $table->text('acao');
            $table->text('tarefa');
            $table->text('responsavel');
            $table->date('prev_inicio');
            $table->date('prev_fim');
            $table->date('real_inicio')->nullable();
            $table->date('real_fim')->nullable();
            $table->text('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('planosacao');
    }
};
