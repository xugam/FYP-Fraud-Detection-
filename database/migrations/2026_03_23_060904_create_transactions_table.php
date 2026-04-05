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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id');
            $table->foreignId('receiver_id');
            $table->decimal('amount', 12, 2);
            $table->enum('status', ['pending', 'success', 'failed', 'flagged'])->default('pending');
            $table->unsignedTinyInteger('risk_score')->default(0);
            $table->text('failure_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
