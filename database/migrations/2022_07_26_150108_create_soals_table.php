<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('soals', function (Blueprint $table) {
            $table->id();
            $table->text('soal');
            $table->unsignedInteger('bobot');
            $table->enum('type', ['pg','pgk','menjodohkan','isian','uraian']); //pg: Pilihan ganda, pgk: Pilihan ganda kompleks
            $table->text('answers');
            $table->text('correctAnswers');
            $table->foreignId('ujian_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('soals');
    }
};
