<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('evaluaciones', function (Blueprint $table) {
            $table->id('id_evaluacion');
            $table->foreignId('id_expo')->constrained('exposiciones', 'id_expo')->onDelete('cascade');
            $table->foreignId('id_usuario')->constrained('usuarios', 'id_usuario')->onDelete('cascade'); // Evaluador
            $table->text('observaciones')->nullable();
            $table->dateTime('fecha');
            $table->timestamps();
        });
    }
    public function down() { Schema::dropIfExists('evaluaciones'); }
};