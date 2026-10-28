<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modul Hazards: Objekte einer Bahn (Bäume, Bunker, Wasser …) und ihre Messungen mit Historie.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('points_of_interest', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hole_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30);
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->decimal('elevation_m', 7, 2)->nullable();
            $table->decimal('position_accuracy_m', 5, 2)->nullable();
            $table->string('position_source', 30)->nullable();
            $table->json('geometry')->nullable();
            $table->unsignedTinyInteger('importance')->default(2);
            $table->string('status', 20)->default('published');
            $table->timestamps();
        });

        Schema::create('measurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('point_of_interest_id')->constrained('points_of_interest')->cascadeOnDelete();
            $table->string('quantity', 30); // height, width, depth, carry_from_tee, distance_to_green
            $table->decimal('value_m', 7, 2);
            $table->decimal('accuracy_m', 5, 2)->nullable();
            $table->string('method', 30)->nullable();
            $table->string('device')->nullable();
            $table->foreignId('tee_set_id')->nullable()->constrained()->nullOnDelete();
            $table->date('measured_at')->nullable();
            $table->foreignId('measured_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamp('superseded_at')->nullable();
            $table->timestamps();

            $table->index(['point_of_interest_id', 'quantity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('measurements');
        Schema::dropIfExists('points_of_interest');
    }
};
