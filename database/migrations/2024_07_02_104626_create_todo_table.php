<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('todo', function (Blueprint $table) {
            $table->id();
            $table->string('namaAO')->nullable();
            $table->string('activity');
            $table->string('jenisKunjungan')->nullable();
            $table->text('keterangan')->nullable();
            $table->string('debiturName')->nullable();
            $table->string('kolektibilitas')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('todo');
    }
};
