<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modul Player (Vorteile mit Konto): private Notizen je Bahn und Runden-Tagebuch.
 * Schlägerdistanzen und Heimatplatz liegen in users.settings (JSON). Alles wird mit dem Konto gelöscht.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hole_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('hole_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();

            $table->unique(['user_id', 'hole_id']);
        });

        Schema::create('rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tee_set_id')->nullable()->constrained()->nullOnDelete();
            $table->date('played_on');
            $table->unsignedTinyInteger('holes_played')->default(18);
            $table->unsignedSmallInteger('score')->nullable();
            $table->string('note', 500)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'played_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rounds');
        Schema::dropIfExists('hole_notes');
    }
};
