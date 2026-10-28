<?php

namespace App\Modules\Hazards\Enums;

/**
 * Objekttypen einer Bahn. Neuer Typ = neuer Fall + Übersetzung in lang/de/hazards.php, keine Migration.
 */
enum PoiType: string
{
    case Tree = 'tree';
    case TreeGroup = 'tree_group';
    case BunkerFairway = 'bunker_fairway';
    case BunkerGreenside = 'bunker_greenside';
    case Water = 'water';
    case OutOfBounds = 'out_of_bounds';
    case DoglegCorner = 'dogleg_corner';
    case LayupZone = 'layup_zone';
    case Shortcut = 'shortcut';
    case Slope = 'slope';
    case Other = 'other';

    public function label(): string
    {
        return __('hazards.types.'.$this->value);
    }

    /** Symbol-Schlüssel für Liste und Marker (public/js/explorer.js, CSS .poi-icon--*). */
    public function icon(): string
    {
        return match ($this) {
            self::Tree, self::TreeGroup => 'tree',
            self::BunkerFairway, self::BunkerGreenside => 'bunker',
            self::Water => 'water',
            self::OutOfBounds => 'oob',
            default => 'point',
        };
    }
}
