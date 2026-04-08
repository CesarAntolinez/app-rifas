<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('raffle_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('raffle_id')->constrained()->cascadeOnDelete();
            $table->timestamp('executed_at');
            $table->unsignedBigInteger('executed_by');
            $table->foreign('executed_by')->references('id')->on('users');
            $table->string('algorithm_version');
            $table->string('seed_info');
            $table->json('payload');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('raffle_audit_logs');
    }
};
