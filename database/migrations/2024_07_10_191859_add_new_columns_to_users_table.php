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
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar')->nullable();
            $table->tinyInteger('admin')->notnull()->default(0);
            $table->tinyInteger('assessor')->notnull()->default(0);
            $table->tinyInteger('p_escola')->notnull()->default(0);
            $table->tinyInteger('coordenador')->notnull()->default(0);
            $table->tinyInteger('coord_nig')->notnull()->default(0);
            $table->tinyInteger('secretaria')->notnull()->default(0);
            $table->tinyInteger('ativo')->notnull()->default(1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
