<?php
declare(strict_types=1);

namespace Bga\Games\WildLife\Managers;

/**
 * Cards Manager: Handle all static card definitions and constants.
 */
class Cards
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

        // Small Life
        $smallAnimals = [
            1 => 'Didi', 2 => 'Didi1', 3 => 'Pacho', 4 => 'Pacho1',
            5 => 'Pacho2', 6 => 'Pacho3', 7 => 'Puki', 8 => 'Umi',
            9 => 'Yawa', 10 => 'Yawa1', 11 => 'Yawa2', 12 => 'Yawa3',
        ];
        foreach ($smallAnimals as $arg => $name) {
            self::$TYPES["small_life_{$arg}"] = [
                'card_type' => 'small_life',
                'card_type_arg' => $arg,
                'name' => $name,
                'category' => 'life',
                'life_type' => 'small_life',
                'points' => 1,
                'image' => "cards/small/{$name}.jpg",
            ];
        }

        // Big Life
        $bigAnimals = [
            1 => 'Fel', 2 => 'Fel1', 3 => 'Koro', 4 => 'Koro1',
            5 => 'Koro2', 6 => 'Koro3', 7 => 'Kuma', 8 => 'Kuma1',
            9 => 'Pua', 10 => 'Pua1', 11 => 'Pua2', 12 => 'Pua3',
        ];
        foreach ($bigAnimals as $arg => $name) {
            self::$TYPES["big_life_{$arg}"] = [
                'card_type' => 'big_life',
                'card_type_arg' => $arg,
                'name' => $name,
                'category' => 'life',
                'life_type' => 'big_life',
                'points' => 2,
                'image' => "cards/big/{$name}.jpg",
            ];
        }

        // Flying Life
        $flyingAnimals = [
            1 => ['name' => 'Robin', 'points' => 1],
            2 => ['name' => 'Robin1', 'points' => 1],
            3 => ['name' => 'Robin2', 'points' => 1],
            4 => ['name' => 'Robin3', 'points' => 1],
            5 => ['name' => 'Calax', 'points' => 1],
            6 => ['name' => 'Calax1', 'points' => 1],
            7 => ['name' => 'Calax2', 'points' => 2],
            8 => ['name' => 'Calax3', 'points' => 2],
            9 => ['name' => 'Roku', 'points' => 2],
            10 => ['name' => 'Roku1', 'points' => 2],
            11 => ['name' => 'Roku2', 'points' => 2],
            12 => ['name' => 'Roku3', 'points' => 2],
        ];
        foreach ($flyingAnimals as $arg => $data) {
            self::$TYPES["flying_life_{$arg}"] = [
                'card_type' => 'flying_life',
                'card_type_arg' => $arg,
                'name' => $data['name'],
                'category' => 'life',
                'life_type' => 'flying_life',
                'points' => $data['points'],
                'image' => "cards/flying/{$data['name']}.jpg",
            ];
        }

        // Aquatic Life
        $aquaticAnimals = [
            1 => 'Axo', 2 => 'Axo1', 3 => 'Axo2', 4 => 'Axo3',
            5 => 'Spock', 6 => 'Spock1', 7 => 'Spock2', 8 => 'Spock3',
            9 => 'Yang', 10 => 'Yang1', 11 => 'Yang2', 12 => 'Yin',
        ];
        foreach ($aquaticAnimals as $arg => $name) {
            self::$TYPES["aquatic_life_{$arg}"] = [
                'card_type' => 'aquatic_life',
                'card_type_arg' => $arg,
                'name' => $name,
                'category' => 'life',
                'life_type' => 'aquatic_life',
                'points' => 0,
                'image' => "cards/aquatic/{$name}.jpg",
            ];
        }

        // Enhancers
        $enhancerTypes = [
            'enhancer_spring' => ['name' => 'Spring', 'multiplier' => 3, 'target' => 'small_life', 'image' => 'cards/enhancers/Primavera.jpg'],
            'enhancer_winter' => ['name' => 'Winter', 'multiplier' => 2, 'target' => 'big_life', 'image' => 'cards/enhancers/Invierno.jpg'],
            'enhancer_nesting' => ['name' => 'Nesting', 'multiplier' => 2, 'target' => 'flying_life', 'image' => 'cards/enhancers/Anidación.jpg'],
            'enhancer_spawning' => ['name' => 'Spawning', 'multiplier' => 2, 'target' => 'aquatic_life', 'image' => 'cards/enhancers/Desove.jpg'],
        ];
        foreach ($enhancerTypes as $type => $data) {
            for ($i = 1; $i <= 4; $i++) {
                self::$TYPES["{$type}_{$i}"] = [
                    'card_type' => $type,
                    'card_type_arg' => $i,
                    'name' => $data['name'],
                    'category' => 'enhancer',
                    'target_life' => $data['target'],
                    'multiplier' => $data['multiplier'],
                    'image' => $data['image'],
                ];
            }
        }

        // Rain
        for ($i = 1; $i <= 5; $i++) {
            self::$TYPES["rain_{$i}"] = [
                'card_type' => 'rain',
                'card_type_arg' => $i,
                'name' => 'Rain',
                'category' => 'rain',
                'points' => 3,
                'image' => 'cards/enhancers/Lluvia.jpg',
            ];
        }

        // Protectors
        for ($i = 1; $i <= 5; $i++) {
            self::$TYPES["protector_{$i}"] = [
                'card_type' => 'protector',
                'card_type_arg' => $i,
                'name' => 'No Hunting',
                'category' => 'protector',
                'image' => 'cards/special/Nohunting.jpg',
            ];
        }

        // Predators
        for ($i = 1; $i <= 14; $i++) {
            self::$TYPES["predator_{$i}"] = [
                'card_type' => 'predator',
                'card_type_arg' => $i,
                'name' => 'Predator',
                'category' => 'aggressor',
                'image' => "cards/threats/predator.jpg",
            ];
        }

        // Hunters
        $hunterImages = [1 => 'hunter1', 2 => 'hunter2', 3 => 'hunter3'];
        foreach ($hunterImages as $arg => $img) {
            self::$TYPES["hunter_{$arg}"] = [
                'card_type' => 'hunter',
                'card_type_arg' => $arg,
                'name' => 'Hunter',
                'category' => 'aggressor',
                'image' => "cards/threats/{$img}.jpg",
            ];
        }

        // Catastrophes
        self::$TYPES['catastrophe_fire_1'] = ['card_type' => 'catastrophe_fire', 'card_type_arg' => 1, 'name' => 'Fire', 'category' => 'catastrophe', 'effect' => 'fire', 'image' => 'cards/catastrophes/incendio.jpg'];
        self::$TYPES['catastrophe_fire_2'] = ['card_type' => 'catastrophe_fire', 'card_type_arg' => 2, 'name' => 'Fire', 'category' => 'catastrophe', 'effect' => 'fire', 'image' => 'cards/catastrophes/incendio.jpg'];
        self::$TYPES['catastrophe_water_1'] = ['card_type' => 'catastrophe_water', 'card_type_arg' => 1, 'name' => 'Pollution', 'category' => 'catastrophe', 'effect' => 'water', 'image' => 'cards/catastrophes/contaminación.jpg'];
        self::$TYPES['catastrophe_both_1'] = ['card_type' => 'catastrophe_both', 'card_type_arg' => 1, 'name' => 'Fire and Pollution', 'category' => 'catastrophe', 'effect' => 'both', 'image' => 'cards/catastrophes/Incendio y contaminación.jpg'];
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
