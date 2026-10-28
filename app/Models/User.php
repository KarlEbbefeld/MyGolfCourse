<?php

namespace App\Models;

use App\Core\Enums\Role;
use App\Modules\Courses\Models\Course;
use App\Modules\Player\Models\HoleNote;
use App\Modules\Player\Models\Round;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Konto. E-Mail-Bestätigung (MustVerifyEmail): Nach der Registrierung kommt ein Link per Mail;
 * bis zur Bestätigung zeigt das Konto einen Hinweis.
 *
 * Community (Modul Community): Nutzername, öffentliches oder privates Profil, Club, Folgen, Blockieren.
 * Freunde = gegenseitiges Folgen.
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    public const UNITS = ['m', 'yd'];

    /** Profilbilder liegen privat (storage/app/private/avatars) und werden über players.avatar ausgeliefert. */
    public const AVATAR_DISK = 'local';

    protected $fillable = [
        'name',
        'email',
        'password',
        'locale',
        'settings',
        'username',
        'bio',
        'handicap',
        'profile_public',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $attributes = [
        'role' => 'user',
        'locale' => 'de',
        'profile_public' => false,
    ];

    /** Für Laufzeit-Abfragen zwischengespeichert (pro Request). */
    private ?array $blockedIdsCache = null;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'settings' => 'array',
            'profile_public' => 'boolean',
            'handicap' => 'float',
        ];
    }

    protected static function booted(): void
    {
        // Profilbild mit dem Konto löschen (DSGVO)
        static::deleting(function (User $user) {
            if ($user->avatar_path) {
                Storage::disk(self::AVATAR_DISK)->delete($user->avatar_path);
            }
        });
    }

    /** Eindeutiger Nutzername aus einem Namen, z. B. „karl-ebbefeld“, „karl-ebbefeld2“. */
    public static function makeUsername(string $source): string
    {
        $base = Str::limit(Str::slug($source) ?: 'golfer', 30, '');
        $username = $base;
        $i = 2;
        while (self::query()->where('username', $username)->exists()) {
            $username = $base.$i++;
        }

        return $username;
    }

    // ---------- Beziehungen ----------

    /** Merkliste (Modul Favorites). */
    public function favoriteCourses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'favorites')->withTimestamps()->orderByPivot('created_at', 'desc');
    }

    /** Zuletzt angesehene Plätze, neueste zuerst. */
    public function viewedCourses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_views')->withPivot('viewed_at')->orderByPivot('viewed_at', 'desc');
    }

    /** Private Notizen je Bahn (Modul Player). */
    public function holeNotes(): HasMany
    {
        return $this->hasMany(HoleNote::class);
    }

    /** Runden-Tagebuch (Modul Player). */
    public function rounds(): HasMany
    {
        return $this->hasMany(Round::class);
    }

    /** Club = Platz, dem sich die Person zuordnet (Selbstauskunft). */
    public function club(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'club_id');
    }

    /** Personen, denen ich folge. */
    public function following(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'follows', 'follower_id', 'followed_id')->withTimestamps();
    }

    /** Personen, die mir folgen. */
    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'follows', 'followed_id', 'follower_id')->withTimestamps();
    }

    /** Freunde: gegenseitiges Folgen. */
    public function friends()
    {
        return $this->following()->whereIn('users.id', DB::table('follows')->select('follower_id')->where('followed_id', $this->id));
    }

    // ---------- Community-Regeln ----------

    public function homeCourseId(): ?int
    {
        return $this->club_id;
    }

    public function isFollowing(User $other): bool
    {
        return DB::table('follows')->where('follower_id', $this->id)->where('followed_id', $other->id)->exists();
    }

    public function isFriendWith(User $other): bool
    {
        return $this->isFollowing($other) && $other->isFollowing($this);
    }

    /** IDs aller Personen, die ich blockiert habe oder die mich blockiert haben. */
    public function blockedIds(): array
    {
        return $this->blockedIdsCache ??= DB::table('blocks')->where('blocker_id', $this->id)->pluck('blocked_id')
            ->merge(DB::table('blocks')->where('blocked_id', $this->id)->pluck('blocker_id'))
            ->unique()->values()->all();
    }

    public function hasBlocked(User $other): bool
    {
        return DB::table('blocks')->where('blocker_id', $this->id)->where('blocked_id', $other->id)->exists();
    }

    public function isBlockedWith(User $other): bool
    {
        return in_array($other->id, $this->blockedIds(), true);
    }

    /** Sieht $viewer Club, Handicap, Bio, Runden und Follower dieses Profils? */
    public function isVisibleTo(?User $viewer): bool
    {
        if ($viewer && $viewer->is($this)) {
            return true;
        }
        if ($viewer && $this->isBlockedWith($viewer)) {
            return false;
        }

        return $this->profile_public || ($viewer && $this->isFriendWith($viewer));
    }

    /** Darf $sender dieser Person eine Direktnachricht schreiben? */
    public function canBeMessagedBy(User $sender): bool
    {
        return ! $sender->is($this)
            && $sender->hasVerifiedEmail()
            && ! $this->isBlockedWith($sender)
            && ($this->profile_public || $this->isFriendWith($sender));
    }

    /**
     * Adresse des Profilbilds für den aktuellen Betrachter – null, wenn keins da ist oder das Profil für ihn privat ist
     * (dann zeigt die Oberfläche die Initialen).
     */
    public function avatarUrl(): ?string
    {
        if (! $this->avatar_path || ! $this->username || ! $this->isVisibleTo(auth()->user())) {
            return null;
        }

        return route('players.avatar', ['user' => $this->username, 'v' => substr(md5($this->avatar_path), 0, 8)]);
    }

    public function deleteAvatar(): void
    {
        if ($this->avatar_path) {
            Storage::disk(self::AVATAR_DISK)->delete($this->avatar_path);
            if ($this->exists) {
                $this->forceFill(['avatar_path' => null])->saveQuietly();
            }
        }
    }

    public function initials(): string
    {
        return collect(preg_split('/\s+/', trim($this->name)))->filter()->take(2)
            ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->join('') ?: '?';
    }

    // ---------- Sonstiges ----------

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function setting(string $key, mixed $default = null): mixed
    {
        return data_get($this->settings, $key, $default);
    }

    public function units(): string
    {
        $units = $this->setting('units', 'm');

        return in_array($units, self::UNITS, true) ? $units : 'm';
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPassword($token));
    }
}
