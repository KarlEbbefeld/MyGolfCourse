<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Felder für die Pflege im Admin: Bahngrafik, Handy-Fassung der Panoramen, Kalibrierung, Lage auf der Bahngrafik.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('holes', function (Blueprint $table) {
            // Bahngrafik des Clubs (Modus „Plan“), Pfad relativ zu public/
            $table->string('plan_image_url')->nullable()->after('poster_image_url');
        });

        Schema::table('panoramas', function (Blueprint $table) {
            // Verkleinerte Fassung (4096 px) für Handys, die keine 8K-Texturen darstellen
            $table->string('image_mobile_url')->nullable()->after('image_url');
            // exif (aus dem Foto), manual (auf der Karte gesetzt), fallback (noch setzen!)
            $table->string('position_source', 20)->default('manual')->after('position_accuracy_m');
            $table->boolean('heading_calibrated')->default(false)->after('heading_deg');
        });

        Schema::table('points_of_interest', function (Blueprint $table) {
            // Lage auf der Bahngrafik, 0–1 relativ zu Breite/Höhe
            $table->decimal('plan_x', 6, 5)->nullable()->after('geometry');
            $table->decimal('plan_y', 6, 5)->nullable()->after('plan_x');
        });
    }

    public function down(): void
    {
        Schema::table('points_of_interest', function (Blueprint $table) {
            $table->dropColumn(['plan_x', 'plan_y']);
        });
        Schema::table('panoramas', function (Blueprint $table) {
            $table->dropColumn(['image_mobile_url', 'position_source', 'heading_calibrated']);
        });
        Schema::table('holes', function (Blueprint $table) {
            $table->dropColumn('plan_image_url');
        });
    }
};
