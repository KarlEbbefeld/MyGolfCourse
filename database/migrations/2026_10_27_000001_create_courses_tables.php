<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modul Courses: Plätze, Abschlag-Sets, Bahnen, Abschläge je Bahn (docs/DATA_MODEL.md).
 * Längen in Metern, Koordinaten WGS84.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('short_description', 300)->nullable();
            $table->text('description')->nullable();
            $table->string('club_name')->nullable();
            $table->string('street')->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('city')->nullable();
            $table->string('region')->nullable()->index();
            $table->char('country', 2)->default('DE');
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->unsignedTinyInteger('holes_count')->default(18);
            $table->unsignedTinyInteger('par_total')->nullable();
            $table->string('website_url')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email')->nullable();
            $table->string('hero_image_url')->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->timestamp('published_at')->nullable();
            $table->date('capture_date')->nullable();
            $table->boolean('is_demo')->default(false);
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('tee_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('color', 20);
            $table->decimal('course_rating', 4, 1)->nullable();
            $table->unsignedSmallInteger('slope')->nullable();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('holes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('number');
            $table->string('name')->nullable();
            $table->unsignedTinyInteger('par');
            $table->unsignedTinyInteger('stroke_index')->nullable();
            $table->text('description')->nullable();
            $table->string('dogleg', 10)->default('none');
            $table->decimal('green_lat', 10, 7)->nullable();
            $table->decimal('green_lng', 10, 7)->nullable();
            $table->decimal('green_elevation_m', 7, 2)->nullable();
            $table->decimal('green_depth_m', 5, 1)->nullable();
            $table->string('poster_image_url')->nullable();
            $table->timestamps();

            $table->unique(['course_id', 'number']);
        });

        Schema::create('hole_tees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hole_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tee_set_id')->constrained()->cascadeOnDelete();
            $table->decimal('length_m', 6, 1);
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->decimal('elevation_m', 7, 2)->nullable();
            $table->decimal('position_accuracy_m', 5, 2)->nullable();
            $table->timestamps();

            $table->unique(['hole_id', 'tee_set_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hole_tees');
        Schema::dropIfExists('holes');
        Schema::dropIfExists('tee_sets');
        Schema::dropIfExists('courses');
    }
};
