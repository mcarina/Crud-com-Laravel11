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
        Schema::create('_coordenadores', function (Blueprint $table) {
            $table->id();
            $table->string('gestao');
            $table->string('coordenadoria');
            $table->string('municipio');
            $table->string('coordenador');
            $table->string('assessor');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('_coordenadores');
    }
};
