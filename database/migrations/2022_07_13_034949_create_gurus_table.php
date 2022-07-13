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
        Schema::create('gurus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('nip')->nullable();
            $table->string('nuptk')->nullable();
            $table->string('jabatan')->nullable();
            $table->string('ttl')->nullable();
            $table->boolean('isMale')->default(true);
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('statusKepegawaian')->nullable();
            $table->string('pendidikanTerakhir')->nullable();            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('gurus');
    }
};
