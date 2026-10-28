<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modul Explorer: 360°-Aufnahmepositionen und ihre Verbindungen (Street-View-Navigation).
 * Bilder vorerst als URL; die Medienverwaltung (media_assets) folgt mit MGC-030.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('panoramas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hole_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            // Leer = der Viewer zeichnet ein Platzhalter-Panorama (vor den ersten echten Aufnahmen)
            $table->string('image_url')->nullable();
            $table->string('kind', 10)->default('aerial'); // aerial (Drohne) oder ground (Augenhöhe)
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->decimal('ground_elevation_m', 7, 2)->default(0);
            $table->decimal('altitude_m', 6, 2)->default(1.6); // Höhe der Kamera über Grund
            $table->decimal('position_accuracy_m', 5, 2)->nullable();
            // In welche Himmelsrichtung zeigt die Bildmitte? 0 = Norden. Wird im Admin kalibriert.
            $table->decimal('heading_deg', 6, 2)->default(0);
            $table->decimal('default_yaw_deg', 6, 2)->nullable(); // leer = Blick zum Grün
            $table->decimal('default_pitch_deg', 5, 2)->default(0);
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->boolean('is_start')->default(false);
            $table->timestamp('captured_at')->nullable();
            $table->timestamps();
        });

        Schema::create('panorama_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_panorama_id')->constrained('panoramas')->cascadeOnDelete();
            $table->foreignId('to_panorama_id')->constrained('panoramas')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['from_panorama_id', 'to_panorama_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('panorama_links');
        Schema::dropIfExists('panoramas');
    }
};
