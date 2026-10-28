<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module Favorites / Accounts: Merkliste und zuletzt angesehene Plätze angemeldeter Nutzer.
 * Beide werden mit dem Konto gelöscht (DSGVO, docs/DATA_MODEL.md → Löschregeln).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favorites', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['user_id', 'course_id']);
        });

        Schema::create('course_views', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->timestamp('viewed_at');

            $table->primary(['user_id', 'course_id']);
            $table->index(['user_id', 'viewed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_views');
        Schema::dropIfExists('favorites');
    }
};
