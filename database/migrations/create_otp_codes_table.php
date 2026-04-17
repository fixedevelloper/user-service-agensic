<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('otp_codes', function (Blueprint $blueprint) {
            $blueprint->id();

// On utilise 'identifier' pour stocker soit l'email soit le téléphone
            $blueprint->string('identifier')->index();

            $blueprint->string('code');

// Pour savoir si le code a déjà été utilisé
            $blueprint->boolean('used')->default(false);

// Date d'expiration (ex: +15 minutes)
            $blueprint->timestamp('expires_at');

            $blueprint->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_codes');
    }
};
