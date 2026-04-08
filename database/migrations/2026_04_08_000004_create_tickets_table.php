<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('raffle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('participant_id')->constrained()->cascadeOnDelete();
            $table->string('serie');
            $table->string('number');
            $table->boolean('is_winner')->default(false);
            $table->timestamps();

            $table->unique(['raffle_id', 'serie', 'number']);
            $table->index('participant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
