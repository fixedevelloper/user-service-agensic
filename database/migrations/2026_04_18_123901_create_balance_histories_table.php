<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('balance_histories', function (Blueprint $table) {
            $table->id();

            // Relation avec l'utilisateur
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Détails financiers
            // On utilise decimal pour éviter les erreurs d'arrondi des floats
            $table->decimal('amount', 16, 8);
            $table->decimal('previous_balance', 16, 8)->nullable();
            $table->decimal('new_balance', 16, 8)->nullable();

            // Type d'opération (deposit, withdrawal, bonus, etc.)
            $table->string('type')->default('deposit');

            // IDEMPOTENCE : La clé de sécurité
            // On stocke la référence unique venant du service Paiement
            $table->string('provider_reference')->unique()->nullable();

            // Informations optionnelles
            $table->string('description')->nullable();
            $table->json('metadata')->nullable(); // Pour stocker le payload brut si besoin

            $table->timestamps();

            // Index pour des recherches rapides
            $table->index('provider_reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('balance_histories');
    }
};
