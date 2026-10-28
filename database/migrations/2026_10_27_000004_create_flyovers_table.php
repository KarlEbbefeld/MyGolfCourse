<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modul Explorer: Drohnenüberflug je Bahn (Modus „Überflug“).
 * Video vorerst als Pfad/URL; die Medienverwaltung mit Streaming-Anbieter folgt (D-007).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flyovers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hole_id')->constrained()->cascadeOnDelete();
            // Relativ zu public/ (z. B. media/demo/bahn-1.mp4) oder volle https-URL
            $table->string('video_url');
            $table->string('poster_url')->nullable();
            $table->string('direction', 20)->default('tee_to_green');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flyovers');
    }
};
