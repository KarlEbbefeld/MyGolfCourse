<?php

namespace App\Modules\Player\Support;

use App\Core\Support\Units;
use App\Models\User;

/**
 * „Mein Bag“: Carry-Distanzen der eigenen Schläger (gespeichert in Metern in users.settings.bag)
 * und daraus abgeleitete Hinweise – Spielplan je Bahn und welcher Schläger an einem Hindernis landet.
 *
 * Bewusst einfache Faustregeln (keine Streuung, kein Roll, kein Wind): Orientierung, keine Garantie.
 */
class Bag
{
    /** Schläger in Reihenfolge vom längsten zum kürzesten. */
    public const CLUBS = [
        'driver' => 'Driver',
        'wood3' => 'Holz 3',
        'wood5' => 'Holz 5',
        'hybrid' => 'Hybrid',
        'iron4' => 'Eisen 4',
        'iron5' => 'Eisen 5',
        'iron6' => 'Eisen 6',
        'iron7' => 'Eisen 7',
        'iron8' => 'Eisen 8',
        'iron9' => 'Eisen 9',
        'pw' => 'Pitching Wedge',
        'gw' => 'Gap Wedge',
        'sw' => 'Sand Wedge',
        'lw' => 'Lob Wedge',
    ];

    /** Toleranz in Metern, innerhalb der ein Schlag „bei einem Hindernis landet“. */
    public const LANDING_WINDOW = 12;

    /** @param  array<string, float>  $carries  Schlüssel aus CLUBS → Meter */
    public function __construct(public readonly array $carries) {}

    public static function for(?User $user): self
    {
        $raw = (array) ($user?->setting('bag') ?? []);
        $carries = [];
        foreach (self::CLUBS as $key => $label) {
            if (isset($raw[$key]) && is_numeric($raw[$key]) && $raw[$key] > 0) {
                $carries[$key] = (float) $raw[$key];
            }
        }
        arsort($carries);

        return new self($carries);
    }

    public function isEmpty(): bool
    {
        return $this->carries === [];
    }

    public static function label(string $key): string
    {
        return self::CLUBS[$key] ?? $key;
    }

    /** „Driver (220 m)“ */
    public function describe(string $key): string
    {
        return self::label($key).' ('.Units::distance($this->carries[$key]).')';
    }

    /**
     * Spielplan für eine Bahnlänge: Abschlag mit dem längsten Schläger (Par 3: passendster Schläger),
     * dann so lange den längsten Fairway-Schläger, bis der Rest erreichbar ist, zum Schluss der passende Schläger.
     *
     * @return list<array{club: string, label: string, carry: float, remaining: float}>
     */
    public function plan(float $length, int $par): array
    {
        if ($this->isEmpty() || $length <= 0) {
            return [];
        }

        if ($par === 3) {
            $club = $this->closestTo($length, array_keys($this->carries));

            return [$this->step($club, max($length - $this->carries[$club], 0))];
        }

        $steps = [];
        $remaining = $length;
        $longest = array_key_first($this->carries);
        $steps[] = $this->step($longest, $remaining = max($remaining - $this->carries[$longest], 0));

        // Fairway-Schläger: alles außer Driver
        $fairway = array_values(array_filter(array_keys($this->carries), fn ($k) => $k !== 'driver'));
        $maxFairway = $fairway ? $this->carries[$fairway[0]] : $this->carries[$longest];

        while ($remaining > $maxFairway + 5 && count($steps) < 3 && $fairway) {
            $steps[] = $this->step($fairway[0], $remaining = max($remaining - $this->carries[$fairway[0]], 0));
        }

        if ($remaining > 15 && count($steps) < 4) {
            $club = $this->closestTo($remaining, $fairway ?: [$longest]);
            $steps[] = $this->step($club, max($remaining - $this->carries[$club], 0));
        }

        return $steps;
    }

    /**
     * Was bedeutet ein Hindernis in Entfernung $distance (vom Abschlag) für den eigenen Abschlag?
     *
     * @return array{lands: list<string>, carries: ?string}  Schläger, die dort landen; kürzester Schläger, der sicher darüber kommt
     */
    public function atDistance(float $distance): array
    {
        $lands = [];
        $carries = null;

        foreach ($this->carries as $key => $carry) {
            if (abs($carry - $distance) <= self::LANDING_WINDOW) {
                $lands[] = $key;
            } elseif ($carry > $distance + self::LANDING_WINDOW) {
                $carries = $key; // Liste ist absteigend sortiert → am Ende steht der kürzeste passende
            }
        }

        return ['lands' => $lands, 'carries' => $carries];
    }

    /** @param  list<string>  $keys */
    private function closestTo(float $distance, array $keys): string
    {
        usort($keys, fn ($a, $b) => abs($this->carries[$a] - $distance) <=> abs($this->carries[$b] - $distance));

        return $keys[0];
    }

    /** @return array{club: string, label: string, carry: float, remaining: float} */
    private function step(string $club, float $remaining): array
    {
        return ['club' => $club, 'label' => self::label($club), 'carry' => $this->carries[$club], 'remaining' => $remaining];
    }
}
