<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Modul Community: Profile, Clubzugehörigkeit, Folgen, Blockieren, Chats (direkt + Club), Meldungen, Mitteilungen.
 * Muster aus dem Golf Marketplace (Polling-Chat, Blockieren/Melden), angepasst auf Clubs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 40)->nullable()->unique()->after('name');
            $table->string('bio', 500)->nullable()->after('username');
            $table->decimal('handicap', 4, 1)->nullable()->after('bio');
            // Club = Platz (courses); Mitgliedschaft ist eine Selbstauskunft.
            // Ohne Fremdschlüssel: SQLite kann ihn nicht nachträglich anlegen; Löschen eines Platzes setzt club_id im Code zurück (Course::booted)
            $table->unsignedBigInteger('club_id')->nullable()->index()->after('handicap');
            // Datenschutzfreundliche Voreinstellung (Art. 25 DSGVO): privat, bis der Nutzer öffentlich schaltet
            $table->boolean('profile_public')->default(false)->after('club_id');
        });

        // Vorhandene Konten: Nutzername erzeugen, bisherigen „Heimatplatz“ als Club übernehmen
        $taken = [];
        foreach (DB::table('users')->orderBy('id')->get() as $user) {
            $base = Str::slug(Str::before($user->email, '@')) ?: 'golfer';
            $base = Str::limit($base, 30, '');
            $username = $base;
            $i = 2;
            while (in_array($username, $taken, true)) {
                $username = $base.$i++;
            }
            $taken[] = $username;

            $settings = json_decode((string) $user->settings, true) ?: [];
            $clubId = isset($settings['home_course_id']) && is_numeric($settings['home_course_id'])
                && DB::table('courses')->where('id', $settings['home_course_id'])->exists() ? (int) $settings['home_course_id'] : null;

            DB::table('users')->where('id', $user->id)->update(['username' => $username, 'club_id' => $clubId]);
        }

        Schema::create('follows', function (Blueprint $table) {
            $table->foreignId('follower_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('followed_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['follower_id', 'followed_id']);
            $table->index('followed_id');
        });

        Schema::create('blocks', function (Blueprint $table) {
            $table->foreignId('blocker_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('blocked_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['blocker_id', 'blocked_id']);
            $table->index('blocked_id');
        });

        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->string('type', 10); // direct, club
            // Club-Chat: ein Chat je Club
            $table->foreignId('course_id')->nullable()->unique()->constrained()->cascadeOnDelete();
            // Direktchat: Teilnehmer als geordnetes Paar (kleinere ID zuerst), damit es je Paar nur einen Chat gibt
            $table->foreignId('user_low_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('user_high_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_message_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['user_low_id', 'user_high_id']);
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            // Absender gelöscht → Nachricht bleibt für die anderen, anonym (wie im Marketplace)
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            // Von der Moderation ausgeblendet
            $table->timestamp('hidden_at')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'id']);
        });

        // Lesestand je Person und Chat (für Ungelesen-Zähler und „Gelesen“)
        Schema::create('conversation_reads', function (Blueprint $table) {
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('last_read_message_id')->default(0);

            $table->primary(['conversation_id', 'user_id']);
        });

        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reportable_type', 20); // user, message
            $table->unsignedBigInteger('reportable_id');
            $table->string('reason', 30);
            $table->string('note', 1000)->nullable();
            $table->string('status', 20)->default('open'); // open, resolved, dismissed
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['reportable_type', 'reportable_id']);
            $table->index('status');
        });

        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('reports');
        Schema::dropIfExists('conversation_reads');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversations');
        Schema::dropIfExists('blocks');
        Schema::dropIfExists('follows');

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropIndex(['club_id']);
            $table->dropColumn(['username', 'bio', 'handicap', 'club_id', 'profile_public']);
        });
    }
};
