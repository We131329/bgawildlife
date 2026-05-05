<?php
declare(strict_types=1);

namespace Bga\Games\WildLife\Managers;

/**
 * Card Manager: Handle all static card definitions and constants.
 */
class CardManager
{
    // Life type ID mappings
    public const LIFE_TYPE_TO_ID = [
        'small_life' => 1,
        'big_life' => 2,
        'flying_life' => 3,
        'aquatic_life' => 4,
    ];

    public const ID_TO_LIFE_TYPE = [
        1 => 'small_life',
        2 => 'big_life',
        3 => 'flying_life',
        4 => 'aquatic_life',
    ];

    // Aquatic scoring table: count => total points
    public const AQUATIC_SCORING = [
        0 => 0,
        1 => 1,
        2 => 3,
        3 => 6,
        4 => 10,
        5 => 15,
    ];

    // Map card_type to enhancer type it applies to
    public const ENHANCER_TARGETS = [
        'enhancer_spring' => 'small_life',
        'enhancer_winter' => 'big_life',
        'enhancer_nesting' => 'flying_life',
        'enhancer_spawning' => 'aquatic_life',
    ];

    // Enhancer multipliers
    public const ENHANCER_MULTIPLIERS = [
        'enhancer_spring' => 3,
        'enhancer_winter' => 2,
        'enhancer_nesting' => 2,
        'enhancer_spawning' => 2,
    ];

    public static array $TYPES = [];

    public static function init(): void
    {
        if (!empty(self::$TYPES)) return;

        // Small Life (12 cards)
        // Fox (4): Col 1 (Indices 0, 3, 6, 9)
        // Squirrel (2): Col 2, R1-2 (Indices 1, 4)
        // Small Squirrel (2): Col 2, R3-4 (Indices 7, 10)
        // Raccoon (4): Col 3 (Indices 2, 5, 8, 11)
        $smallMapping = [
            1 => ['name' => 'Fox', 'index' => 0], 2 => ['name' => 'Fox', 'index' => 3],
            3 => ['name' => 'Fox', 'index' => 6], 4 => ['name' => 'Fox', 'index' => 9],
            5 => ['name' => 'Squirrel', 'index' => 1], 6 => ['name' => 'Squirrel', 'index' => 4],
            7 => ['name' => 'Small Squirrel', 'index' => 7], 8 => ['name' => 'Small Squirrel', 'index' => 10],
            9 => ['name' => 'Raccoon', 'index' => 2], 10 => ['name' => 'Raccoon', 'index' => 5],
            11 => ['name' => 'Raccoon', 'index' => 8], 12 => ['name' => 'Raccoon', 'index' => 11],
        ];
        foreach ($smallMapping as $arg => $data) {
            self::$TYPES["small_life_{$arg}"] = [
                'card_type' => 'small_life',
                'card_type_arg' => $arg,
                'name' => $data['name'],
                'category' => 'life',
                'life_type' => 'small_life',
                'points' => 1,
                'sprite' => 'small.png',
                'sprite_index' => $data['index'],
            ];
        }

        // Big Life (12 cards)
        // Bear (4): Col 1 (0, 3, 6, 9)
        // Elk (2): Col 2, R1-2 (1, 4)
        // Deer (2): Col 2, R3-4 (7, 10)
        // Boar (4): Col 3 (2, 5, 8, 11)
        $bigMapping = [
            1 => ['name' => 'Bear', 'index' => 0], 2 => ['name' => 'Bear', 'index' => 3],
            3 => ['name' => 'Bear', 'index' => 6], 4 => ['name' => 'Bear', 'index' => 9],
            5 => ['name' => 'Elk', 'index' => 1], 6 => ['name' => 'Elk', 'index' => 4],
            7 => ['name' => 'Deer', 'index' => 7], 8 => ['name' => 'Deer', 'index' => 10],
            9 => ['name' => 'Boar', 'index' => 2], 10 => ['name' => 'Boar', 'index' => 5],
            11 => ['name' => 'Boar', 'index' => 8], 12 => ['name' => 'Boar', 'index' => 11],
        ];
        foreach ($bigMapping as $arg => $data) {
            self::$TYPES["big_life_{$arg}"] = [
                'card_type' => 'big_life',
                'card_type_arg' => $arg,
                'name' => $data['name'],
                'category' => 'life',
                'life_type' => 'big_life',
                'points' => 2,
                'sprite' => 'big.png',
                'sprite_index' => $data['index'],
            ];
        }

        // Flying Life (12 cards)
        // Owl (2): Col 1, R1-2 (0, 3)
        // Barn Owl (2): Col 1, R3-4 (6, 9)
        // Eagle (4): Col 2 (1, 4, 7, 10)
        // Robin (4): Col 3 (2, 5, 8, 11)
        $flyingMapping = [
            1 => ['name' => 'Owl', 'index' => 0, 'points' => 2], 2 => ['name' => 'Owl', 'index' => 3, 'points' => 2],
            3 => ['name' => 'Barn Owl', 'index' => 6, 'points' => 2], 4 => ['name' => 'Barn Owl', 'index' => 9, 'points' => 2],
            5 => ['name' => 'Eagle', 'index' => 1, 'points' => 1], 6 => ['name' => 'Eagle', 'index' => 4, 'points' => 1],
            7 => ['name' => 'Eagle', 'index' => 7, 'points' => 2], 8 => ['name' => 'Eagle', 'index' => 10, 'points' => 2],
            9 => ['name' => 'Robin', 'index' => 2, 'points' => 1], 10 => ['name' => 'Robin', 'index' => 5, 'points' => 1],
            11 => ['name' => 'Robin', 'index' => 8, 'points' => 1], 12 => ['name' => 'Robin', 'index' => 11, 'points' => 1],
        ];
        foreach ($flyingMapping as $arg => $data) {
            self::$TYPES["flying_life_{$arg}"] = [
                'card_type' => 'flying_life',
                'card_type_arg' => $arg,
                'name' => $data['name'],
                'category' => 'life',
                'life_type' => 'flying_life',
                'points' => $data['points'],
                'sprite' => 'flying.png',
                'sprite_index' => $data['index'],
            ];
        }

        // Aquatic Life (12 cards)
        // White Koi (2): Col 1, R1 & R4 (0, 9)
        // Orange Koi (2): Col 1, R2 & R3 (3, 6)
        // Axolotl (4): Col 2 (1, 4, 7, 10)
        // Salmon (4): Col 3 (2, 5, 8, 11)
        $aquaticMapping = [
            1 => ['name' => 'White Koi', 'index' => 0], 2 => ['name' => 'White Koi', 'index' => 9],
            3 => ['name' => 'Orange Koi', 'index' => 3], 4 => ['name' => 'Orange Koi', 'index' => 6],
            5 => ['name' => 'Axolotl', 'index' => 1], 6 => ['name' => 'Axolotl', 'index' => 4],
            7 => ['name' => 'Axolotl', 'index' => 7], 8 => ['name' => 'Axolotl', 'index' => 10],
            9 => ['name' => 'Salmon', 'index' => 2], 10 => ['name' => 'Salmon', 'index' => 5],
            11 => ['name' => 'Salmon', 'index' => 8], 12 => ['name' => 'Salmon', 'index' => 11],
        ];
        foreach ($aquaticMapping as $arg => $data) {
            self::$TYPES["aquatic_life_{$arg}"] = [
                'card_type' => 'aquatic_life',
                'card_type_arg' => $arg,
                'name' => $data['name'],
                'category' => 'life',
                'life_type' => 'aquatic_life',
                'points' => 0,
                'sprite' => 'aquatic.png',
                'sprite_index' => $data['index'],
            ];
        }

        // Enhancers (16 cards)
        // Col 1: Small, Col 2: Big, Col 3: Flying, Col 4: Aquatic
        $enhancerMapping = [
            'enhancer_spring' => ['name' => 'Spring', 'col' => 0, 'target' => 'small_life', 'mult' => 3],
            'enhancer_winter' => ['name' => 'Winter', 'col' => 1, 'target' => 'big_life', 'mult' => 2],
            'enhancer_nesting' => ['name' => 'Nesting', 'col' => 2, 'target' => 'flying_life', 'mult' => 2],
            'enhancer_spawning' => ['name' => 'Spawning', 'col' => 3, 'target' => 'aquatic_life', 'mult' => 2],
        ];
        foreach ($enhancerMapping as $type => $data) {
            for ($row = 0; $row < 4; $row++) {
                $arg = $row + 1;
                $index = $row * 4 + $data['col'];
                self::$TYPES["{$type}_{$arg}"] = [
                    'card_type' => $type,
                    'card_type_arg' => $arg,
                    'name' => $data['name'],
                    'category' => 'enhancer',
                    'target_life' => $data['target'],
                    'multiplier' => $data['mult'],
                    'sprite' => 'enhancers.png',
                    'sprite_index' => $index,
                ];
            }
        }

        // Rain (5 cards)
        for ($i = 1; $i <= 5; $i++) {
            self::$TYPES["rain_{$i}"] = [
                'card_type' => 'rain',
                'card_type_arg' => $i,
                'name' => 'Rain',
                'category' => 'rain',
                'points' => 3,
                'sprite' => 'special.png',
                'sprite_index' => 1, // Col 2
            ];
        }

        // Protectors (5 cards)
        for ($i = 1; $i <= 5; $i++) {
            self::$TYPES["protector_{$i}"] = [
                'card_type' => 'protector',
                'card_type_arg' => $i,
                'name' => 'No Hunting',
                'category' => 'protector',
                'sprite' => 'special.png',
                'sprite_index' => 2, // Col 3
            ];
        }

        // Predators (14 cards)
        for ($i = 1; $i <= 14; $i++) {
            $index = (($i - 1) % 4) * 4; // Col 1 repeat
            self::$TYPES["predator_{$i}"] = [
                'card_type' => 'predator',
                'card_type_arg' => $i,
                'name' => 'Predator',
                'category' => 'aggressor',
                'sprite' => 'threats.png',
                'sprite_index' => $index,
            ];
        }

        // Hunters (8 cards)
        // Cols 2 & 3 (Indices 1, 5, 9, 13 and 2, 6, 10, 14)
        for ($i = 1; $i <= 8; $i++) {
            $col = (($i - 1) % 2) + 1; // Col 2 or 3 (index 1 or 2)
            $row = floor(($i - 1) / 2);
            $index = $row * 4 + $col;
            self::$TYPES["hunter_{$i}"] = [
                'card_type' => 'hunter',
                'card_type_arg' => $i,
                'name' => 'Hunter',
                'category' => 'aggressor',
                'sprite' => 'threats.png',
                'sprite_index' => $index,
            ];
        }

        // Catastrophes (4 cards)
        self::$TYPES['catastrophe_fire_1'] = ['card_type' => 'catastrophe_fire', 'card_type_arg' => 1, 'name' => 'Fire', 'category' => 'catastrophe', 'effect' => 'fire', 'sprite' => 'threats.png', 'sprite_index' => 3];
        self::$TYPES['catastrophe_fire_2'] = ['card_type' => 'catastrophe_fire', 'card_type_arg' => 2, 'name' => 'Fire', 'category' => 'catastrophe', 'effect' => 'fire', 'sprite' => 'threats.png', 'sprite_index' => 3];
        self::$TYPES['catastrophe_water_1'] = ['card_type' => 'catastrophe_water', 'card_type_arg' => 1, 'name' => 'Pollution', 'category' => 'catastrophe', 'effect' => 'water', 'sprite' => 'threats.png', 'sprite_index' => 11];
        self::$TYPES['catastrophe_both_1'] = ['card_type' => 'catastrophe_both', 'card_type_arg' => 1, 'name' => 'Fire and Pollution', 'category' => 'catastrophe', 'effect' => 'both', 'sprite' => 'threats.png', 'sprite_index' => 7];
    }

    public static function getInfo(string $type, int $arg): ?array
    {
        return self::$TYPES["{$type}_{$arg}"] ?? null;
    }

    public static function getCategory(string $cardType): string
    {
        if (in_array($cardType, ['small_life', 'big_life', 'flying_life', 'aquatic_life'])) return 'life';
        if (str_starts_with($cardType, 'enhancer_')) return 'enhancer';
        if ($cardType === 'rain') return 'rain';
        if ($cardType === 'protector') return 'protector';
        if ($cardType === 'predator' || $cardType === 'hunter') return 'aggressor';
        if (str_starts_with($cardType, 'catastrophe_')) return 'catastrophe';
        return 'unknown';
    }

    public static function isLife(string $cardType): bool
    {
        return self::getCategory($cardType) === 'life';
    }
}
