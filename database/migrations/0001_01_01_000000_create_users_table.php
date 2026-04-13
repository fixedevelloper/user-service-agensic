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
        Schema::create('countries', function (Blueprint $table) {
            $table->id("id");
            $table->string('name')->unique();
            $table->string('iso')->unique();
            $table->string('iso3')->nullable();
            $table->string('flag')->nullable();
            $table->integer('phonecode')->nullable();
            $table->string('currency')->nullable();
            $table->integer('status');
            $table->timestamps();
        });

        Schema::create('operators', function (Blueprint $table) {
            $table->id("id");
            $table->string('name')->nullable();
            $table->string('code')->nullable();
            $table->string('logo')->nullable();
            $table->integer('status');
            $table->foreignId('country_id')->nullable()->index();
            $table->timestamps();
        });
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->unique();
            $table->string('email')->nullable();
            $table->foreignId('country_id')->nullable()->index();
            $table->decimal('balance', 50, 18)->default(0);
            $table->decimal('available_balance', 50, 18)->default(0);
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->enum('role', ['customer','admin'])->default('customer');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('icon')->nullable();
            $table->string('route'); // 🔥 navigation mobile/web
            $table->boolean('is_active')->default(true); // 🔥 activer/désactiver
            $table->integer('position')->default(0); // 🔥 ordre affichage
            $table->string('category')->nullable(); // optionnel (Finance, IA…)
            $table->string('color')->nullable();
            $table->timestamps();
        });

        Schema::create('deposits', function (Blueprint $table) {
            $table->id("id");
            $table->string('amount');

            $table->foreignId('operator_id')
                ->nullable()
                ->constrained('operators')
                ->onDelete('set null')
                ->comment('FK to operators');

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null')
                ->comment('FK to users');

            $table->string('reference')->nullable();
            $table->enum('status', ['pending', 'success', 'failed']);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('kyc_documents', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->index();
            // Type de document (CNI, Passeport, Permis, Selfie...)
            $table->string('doc_type', 50);

            // Référence du document (numéro CNI / passeport)
            $table->string('doc_reference')->nullable();

            // Fichiers
            $table->string('front_image')->nullable();   // Recto
            $table->string('back_image')->nullable();    // Verso
            $table->string('selfie_image')->nullable();  // Selfie
            $table->string('proof_address')->nullable(); // Facture / Certificat domicile

            // Statut de validation
            $table->string('status', 30)->default('PENDING'); // PENDING, APPROVED, REJECTED
            $table->text('notes')->nullable();

            // Suivi
            $table->timestamp('submitted_at')->useCurrent();
            $table->timestamp('verified_at')->nullable();
            $table->integer('reviewer_id')->nullable();

        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('services');
        Schema::dropIfExists('sessions');
    }
};
