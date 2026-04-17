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
        Schema::create('deposit_ussds', function (Blueprint $table) {
            $table->id();
            // Utiliser decimal pour l'argent (évite les erreurs d'arrondi des strings ou floats)
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('XAF'); // Important pour savoir quelle monnaie est déposée

            $table->string('country_code', 5); // Correction de la faute de frappe "countr_code"
            $table->string('ussd_code'); // Le code composé (ex: *150*6*...)

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');

            // Référence unique pour éviter les doublons de traitement
            $table->string('reference')->unique()->nullable();
            $table->string('proof')->nullable(); // URL de la capture d'écran si nécessaire

            $table->enum('status', ['pending', 'processing', 'success', 'failed'])->default('pending');

            $table->text('admin_note')->nullable(); // Pour expliquer pourquoi un dépôt a été rejeté
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deposit_ussds');
    }
};
