<?php
/**
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * WildLife: The Card Game implementation
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 */
declare(strict_types=1);

namespace Bga\Games\WildLife;

use Bga\Games\WildLife\States\PlayerTurn;
use Bga\Games\WildLife\States\NewCycle;
use Bga\Games\WildLife\States\MulliganPhase;

class Game extends \Bga\GameFramework\Table
{
    /** @var \Bga\GameFramework\Components\Deck */
    public $cards;

    // Global variable IDs
    const GV_CURRENT_CYCLE = 'current_cycle';
    const GV_TOTAL_CYCLES = 'total_cycles';
    const GV_CARDS_PLAYED_THIS_CYCLE = 'cards_played_this_cycle';
    const GV_FIRST_PLAYER = 'first_player';
    const GV_PENDING_HUNTER_PLAYER = 'pending_hunter_player';
    const GV_PENDING_HUNTER_TARGET = 'pending_hunter_target';
    const GV_PENDING_HUNTER_LIFETYPE = 'pending_hunter_lifetype';
    const GV_ACTIVE_TURN_PLAYER = 'active_turn_player';

    // Cycles per player count
    const CYCLES_BY_PLAYERS = [
        2 => 8,
        3 => 6,
        4 => 4,
        5 => 5,
    ];

    // Aquatic scoring table: count => total points
    const AQUATIC_SCORING = [
        0 => 0,
        1 => 1,
        2 => 3,
        3 => 6,
        4 => 10,
        5 => 15,
    ];

    // Card type definitions with images and properties
    public static array $CARD_TYPES = [];

    // Life type ID mappings
    const LIFE_TYPE_TO_ID = [
        'small_life' => 1,
        'big_life' => 2,
        'flying_life' => 3,
        'aquatic_life' => 4,
    ];

    const ID_TO_LIFE_TYPE = [
        1 => 'small_life',
        2 => 'big_life',
        3 => 'flying_life',
        4 => 'aquatic_life',
    ];

    // Map card_type to enhancer type it applies to
    const ENHANCER_TARGETS = [
        'enhancer_spring' => 'small_life',
        'enhancer_winter' => 'big_life',
        'enhancer_nesting' => 'flying_life',
        'enhancer_spawning' => 'aquatic_life',
    ];

    // Enhancer multipliers
    const ENHANCER_MULTIPLIERS = [
        'enhancer_spring' => 3,
        'enhancer_winter' => 2,
        'enhancer_nesting' => 2,
        'enhancer_spawning' => 2,
    ];

    public function __construct()
    {
        parent::__construct();

        $this->initGameStateLabels([
            self::GV_CURRENT_CYCLE => 10,
            self::GV_TOTAL_CYCLES => 11,
            self::GV_CARDS_PLAYED_THIS_CYCLE => 12,
            self::GV_FIRST_PLAYER => 13,
            self::GV_PENDING_HUNTER_PLAYER => 14,
            self::GV_PENDING_HUNTER_TARGET => 15,
            self::GV_PENDING_HUNTER_LIFETYPE => 16,
            self::GV_ACTIVE_TURN_PLAYER => 17,
        ]);

        $this->cards = $this->bga->deckFactory->createDeck("card");

        self::initCardTypes();
    }

    private static function initCardTypes(): void
    {
        if (!empty(self::$CARD_TYPES)) return;

        // Small Life - 12 cards, 1 point each
        $smallAnimals = [
            1 => 'Didi', 2 => 'Didi1', 3 => 'Pacho', 4 => 'Pacho1',
            5 => 'Pacho2', 6 => 'Pacho3', 7 => 'Puki', 8 => 'Umi',
            9 => 'Yawa', 10 => 'Yawa1', 11 => 'Yawa2', 12 => 'Yawa3',
        ];
        foreach ($smallAnimals as $arg => $name) {
            self::$CARD_TYPES["small_life_{$arg}"] = [
                'card_type' => 'small_life',
                'card_type_arg' => $arg,
                'name' => $name,
                'category' => 'life',
                'life_type' => 'small_life',
                'points' => 1,
                'image' => "cards/small/{$name}.jpg",
            ];
        }

        // Big Life - 12 cards, 2 points each
        $bigAnimals = [
            1 => 'Fel', 2 => 'Fel1', 3 => 'Koro', 4 => 'Koro1',
            5 => 'Koro2', 6 => 'Koro3', 7 => 'Kuma', 8 => 'Kuma1',
            9 => 'Pua', 10 => 'Pua1', 11 => 'Pua2', 12 => 'Pua3',
        ];
        foreach ($bigAnimals as $arg => $name) {
            self::$CARD_TYPES["big_life_{$arg}"] = [
                'card_type' => 'big_life',
                'card_type_arg' => $arg,
                'name' => $name,
                'category' => 'life',
                'life_type' => 'big_life',
                'points' => 2,
                'image' => "cards/big/{$name}.jpg",
            ];
        }

        // Flying Life - 12 cards, 1 or 2 points
        // Robin & Calax = 1pt, Roku = 2pt (6 with 1pt, 6 with 2pt based on user info: 50/50)
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
            self::$CARD_TYPES["flying_life_{$arg}"] = [
                'card_type' => 'flying_life',
                'card_type_arg' => $arg,
                'name' => $data['name'],
                'category' => 'life',
                'life_type' => 'flying_life',
                'points' => $data['points'],
                'image' => "cards/flying/{$data['name']}.jpg",
            ];
        }

        // Aquatic Life - 12 cards, points scale with count
        $aquaticAnimals = [
            1 => 'Axo', 2 => 'Axo1', 3 => 'Axo2', 4 => 'Axo3',
            5 => 'Spock', 6 => 'Spock1', 7 => 'Spock2', 8 => 'Spock3',
            9 => 'Yang', 10 => 'Yang1', 11 => 'Yang2', 12 => 'Yin',
        ];
        foreach ($aquaticAnimals as $arg => $name) {
            self::$CARD_TYPES["aquatic_life_{$arg}"] = [
                'card_type' => 'aquatic_life',
                'card_type_arg' => $arg,
                'name' => $name,
                'category' => 'life',
                'life_type' => 'aquatic_life',
                'points' => 0, // calculated dynamically
                'image' => "cards/aquatic/{$name}.jpg",
            ];
        }

        // Enhancers - 4 of each type = 16 total
        $enhancerTypes = [
            'enhancer_spring' => ['name' => clienttranslate('Primavera'), 'multiplier' => 3, 'target' => 'small_life', 'image' => 'cards/enhancers/Primavera.jpg'],
            'enhancer_winter' => ['name' => clienttranslate('Invierno'), 'multiplier' => 2, 'target' => 'big_life', 'image' => 'cards/enhancers/Invierno.jpg'],
            'enhancer_nesting' => ['name' => clienttranslate('Anidación'), 'multiplier' => 2, 'target' => 'flying_life', 'image' => 'cards/enhancers/Anidación.jpg'],
            'enhancer_spawning' => ['name' => clienttranslate('Desove'), 'multiplier' => 2, 'target' => 'aquatic_life', 'image' => 'cards/enhancers/Desove.jpg'],
        ];
        foreach ($enhancerTypes as $type => $data) {
            for ($i = 1; $i <= 4; $i++) {
                self::$CARD_TYPES["{$type}_{$i}"] = [
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

        // Rain - 5 cards, 3 points each (temporary)
        for ($i = 1; $i <= 5; $i++) {
            self::$CARD_TYPES["rain_{$i}"] = [
                'card_type' => 'rain',
                'card_type_arg' => $i,
                'name' => clienttranslate('Lluvia'),
                'category' => 'rain',
                'points' => 3,
                'image' => 'cards/enhancers/Lluvia.jpg',
            ];
        }

        // Protectors - 5 cards
        for ($i = 1; $i <= 5; $i++) {
            self::$CARD_TYPES["protector_{$i}"] = [
                'card_type' => 'protector',
                'card_type_arg' => $i,
                'name' => clienttranslate('No Hunting'),
                'category' => 'protector',
                'image' => 'cards/special/Nohunting.jpg',
            ];
        }

        // Predators - 14 cards
        $predatorImages = [
            1 => 'predator', 2 => 'predator', 3 => 'predator', 4 => 'predator',
            5 => 'predator', 6 => 'predator', 7 => 'predator', 8 => 'predator',
            9 => 'predator', 10 => 'predator', 11 => 'predator', 12 => 'predator',
            13 => 'predator', 14 => 'predator',
        ];
        foreach ($predatorImages as $arg => $img) {
            self::$CARD_TYPES["predator_{$arg}"] = [
                'card_type' => 'predator',
                'card_type_arg' => $arg,
                'name' => clienttranslate('Depredador'),
                'category' => 'aggressor',
                'image' => "cards/threats/{$img}.jpg",
            ];
        }

        // Hunters - 3 cards
        $hunterImages = [1 => 'hunter1', 2 => 'hunter2', 3 => 'hunter3'];
        foreach ($hunterImages as $arg => $img) {
            self::$CARD_TYPES["hunter_{$arg}"] = [
                'card_type' => 'hunter',
                'card_type_arg' => $arg,
                'name' => clienttranslate('Cazador'),
                'category' => 'aggressor',
                'image' => "cards/threats/{$img}.jpg",
            ];
        }

        // Catastrophes - 4 cards
        self::$CARD_TYPES['catastrophe_fire_1'] = [
            'card_type' => 'catastrophe_fire',
            'card_type_arg' => 1,
            'name' => clienttranslate('Incendio'),
            'category' => 'catastrophe',
            'effect' => 'fire',
            'image' => 'cards/catastrophes/incendio.jpg',
        ];
        self::$CARD_TYPES['catastrophe_fire_2'] = [
            'card_type' => 'catastrophe_fire',
            'card_type_arg' => 2,
            'name' => clienttranslate('Incendio'),
            'category' => 'catastrophe',
            'effect' => 'fire',
            'image' => 'cards/catastrophes/incendio.jpg',
        ];
        self::$CARD_TYPES['catastrophe_water_1'] = [
            'card_type' => 'catastrophe_water',
            'card_type_arg' => 1,
            'name' => clienttranslate('Contaminación'),
            'category' => 'catastrophe',
            'effect' => 'water',
            'image' => 'cards/catastrophes/contaminación.jpg',
        ];
        self::$CARD_TYPES['catastrophe_both_1'] = [
            'card_type' => 'catastrophe_both',
            'card_type_arg' => 1,
            'name' => clienttranslate('Incendio y Contaminación'),
            'category' => 'catastrophe',
            'effect' => 'both',
            'image' => 'cards/catastrophes/Incendio y contaminación.jpg',
        ];
    }

    /**
     * Get card type info by card_type and card_type_arg
     */
    public static function getCardTypeInfo(string $cardType, int $cardTypeArg): ?array
    {
        $key = "{$cardType}_{$cardTypeArg}";
        return self::$CARD_TYPES[$key] ?? null;
    }

    /**
     * Get the category of a card type (life, enhancer, rain, protector, aggressor, catastrophe)
     */
    public static function getCardCategory(string $cardType): string
    {
        if (in_array($cardType, ['small_life', 'big_life', 'flying_life', 'aquatic_life'])) return 'life';
        if (str_starts_with($cardType, 'enhancer_')) return 'enhancer';
        if ($cardType === 'rain') return 'rain';
        if ($cardType === 'protector') return 'protector';
        if ($cardType === 'predator' || $cardType === 'hunter') return 'aggressor';
        if (str_starts_with($cardType, 'catastrophe_')) return 'catastrophe';
        return 'unknown';
    }

    /**
     * Check if a card type is a life type
     */
    public static function isLifeType(string $cardType): bool
    {
        return in_array($cardType, ['small_life', 'big_life', 'flying_life', 'aquatic_life']);
    }

    /**
     * Get the enhancer type that targets a given life type
     */
    public static function getEnhancerForLifeType(string $lifeType): ?string
    {
        return array_search($lifeType, self::ENHANCER_TARGETS) ?: null;
    }

    /**
     * Helper: Discard life cards of specific types and their associated enhancers from a player's habitat.
     */
    public function discardLifeAndEnhancers(int $playerId, array $lifeTypesToRemove): array
    {
        $removedCards = [];
        $habitat = $this->cards->getCardsInLocation('habitat', $playerId);

        foreach ($habitat as $card) {
            $ct = $card['type'];
            $shouldRemove = false;

            // Check if it's a life type to remove
            if (in_array($ct, $lifeTypesToRemove)) {
                $shouldRemove = true;
            }

            // Check if it's an enhancer targeting a life type to remove
            if (str_starts_with($ct, 'enhancer_')) {
                $targetLife = self::ENHANCER_TARGETS[$ct] ?? null;
                if ($targetLife && in_array($targetLife, $lifeTypesToRemove)) {
                    $shouldRemove = true;
                }
            }

            if ($shouldRemove) {
                $this->cards->moveCard((int)$card['id'], 'discard');
                $removedCards[] = $card;
            }
        }

        return $removedCards;
    }

    /**
     * Calculate score for a player's habitat
     */
    public function calculateHabitatScore(int $playerId): int
    {
        $habitatCards = $this->cards->getCardsInLocation('habitat', $playerId);
        $score = 0;

        // Group cards by type
        $byType = [];
        foreach ($habitatCards as $card) {
            $type = $card['type'];
            if (!isset($byType[$type])) $byType[$type] = [];
            $byType[$type][] = $card;
        }

        // Check for enhancers
        $hasEnhancer = [];
        foreach (self::ENHANCER_TARGETS as $enhType => $lifeType) {
            $hasEnhancer[$lifeType] = isset($byType[$enhType]) && count($byType[$enhType]) > 0;
        }

        // Score small life
        $smallCount = count($byType['small_life'] ?? []);
        $smallPoints = $smallCount * 1; // 1 point each
        if ($hasEnhancer['small_life']) $smallPoints *= self::ENHANCER_MULTIPLIERS['enhancer_spring'];
        $score += $smallPoints;

        // Score big life
        $bigCount = count($byType['big_life'] ?? []);
        $bigPoints = $bigCount * 2; // 2 points each
        if ($hasEnhancer['big_life']) $bigPoints *= self::ENHANCER_MULTIPLIERS['enhancer_winter'];
        $score += $bigPoints;

        // Score flying life (variable points per card)
        $flyingCards = $byType['flying_life'] ?? [];
        $flyingPoints = 0;
        foreach ($flyingCards as $card) {
            $info = self::getCardTypeInfo($card['type'], (int)$card['type_arg']);
            $flyingPoints += $info['points'] ?? 1;
        }
        if ($hasEnhancer['flying_life']) $flyingPoints *= self::ENHANCER_MULTIPLIERS['enhancer_nesting'];
        $score += $flyingPoints;

        // Score aquatic life (scales with count)
        $aquaticCount = count($byType['aquatic_life'] ?? []);
        $aquaticPoints = self::AQUATIC_SCORING[min($aquaticCount, 5)] ?? 0;
        if ($hasEnhancer['aquatic_life']) $aquaticPoints *= self::ENHANCER_MULTIPLIERS['enhancer_spawning'];
        $score += $aquaticPoints;

        // Score rain (3 points each, temporary)
        $rainCount = count($byType['rain'] ?? []);
        $score += $rainCount * 3;

        return $score;
    }

    /**
     * Compute and return the current game progression (0-100).
     */
    public function getGameProgression()
    {
        $currentCycle = (int)$this->getGameStateValue(self::GV_CURRENT_CYCLE);
        $totalCycles = (int)$this->getGameStateValue(self::GV_TOTAL_CYCLES);
        if ($totalCycles <= 0) return 0;
        return min(100, (int)(($currentCycle - 1) * 100 / $totalCycles));
    }

    public function upgradeTableDb($from_version)
    {
    }

    /**
     * Gather all information about current game situation (visible by the current player).
     */
    protected function getAllDatas(int $currentPlayerId): array
    {
        $result = [];

        // Player info
        $result["players"] = $this->getCollectionFromDb(
            "SELECT `player_id` AS `id`, `player_score` AS `score`, `player_name` AS `name`, `player_color` AS `color` FROM `player`"
        );

        // Current player's hand
        $result["hand"] = array_values($this->cards->getCardsInLocation('hand', $currentPlayerId));

        // All habitats (public information)
        $result["habitats"] = [];
        foreach (array_keys($result["players"]) as $pid) {
            $result["habitats"][$pid] = array_values($this->cards->getCardsInLocation('habitat', (int)$pid));
        }

        // Deck and discard counts
        $result["deckCount"] = $this->cards->countCardInLocation('deck');
        $result["discardCount"] = $this->cards->countCardInLocation('discard');

        // Game state info
        $result["currentCycle"] = $this->getGameStateValue(self::GV_CURRENT_CYCLE);
        $result["totalCycles"] = $this->getGameStateValue(self::GV_TOTAL_CYCLES);
        $result["cardsPlayedThisCycle"] = $this->getGameStateValue(self::GV_CARDS_PLAYED_THIS_CYCLE);
        $result["firstPlayer"] = $this->getGameStateValue(self::GV_FIRST_PLAYER);

        // Card type definitions (for JS to know card properties)
        $result["cardTypes"] = self::$CARD_TYPES;

        // Hand counts for other players (so you can see how many cards others have)
        $result["handCounts"] = [];
        foreach (array_keys($result["players"]) as $pid) {
            $result["handCounts"][$pid] = $this->cards->countCardInLocation('hand', (int)$pid);
        }

        return $result;
    }

    /**
     * Setup the initial game: create cards, deal hands, set globals.
     */
    protected function setupNewGame($players, $options = [])
    {
        // Create players
        $gameinfos = $this->getGameinfos();
        $default_colors = $gameinfos['player_colors'];

        foreach ($players as $player_id => $player) {
            $query_values[] = vsprintf("(%s, '%s', '%s')", [
                $player_id,
                array_shift($default_colors),
                addslashes($player["player_name"]),
            ]);
        }

        static::DbQuery(
            sprintf(
                "INSERT INTO `player` (`player_id`, `player_color`, `player_name`) VALUES %s",
                implode(",", $query_values)
            )
        );

        $this->reattributeColorsBasedOnPreferences($players, $gameinfos["player_colors"]);
        $this->reloadPlayersBasicInfos();

        // Create the deck of 102 cards
        $cards = [];
        foreach (self::$CARD_TYPES as $key => $cardDef) {
            $cards[] = [
                'type' => $cardDef['card_type'],
                'type_arg' => $cardDef['card_type_arg'],
                'nbr' => 1,
            ];
        }

        $this->cards = $this->bga->deckFactory->createDeck("card");
        $this->cards->createCards($cards, 'deck');
        $this->cards->shuffle('deck');

        // Deal 6 cards to each player
        foreach (array_keys($players) as $player_id) {
            $this->cards->pickCards(6, 'deck', $player_id);
        }

        // Set game state initial values
        $playerCount = count($players);
        $totalCycles = self::CYCLES_BY_PLAYERS[$playerCount] ?? 5;

        $this->setGameStateInitialValue(self::GV_CURRENT_CYCLE, 1);
        $this->setGameStateInitialValue(self::GV_TOTAL_CYCLES, $totalCycles);
        $this->setGameStateInitialValue(self::GV_CARDS_PLAYED_THIS_CYCLE, 0);
        $this->setGameStateInitialValue(self::GV_PENDING_HUNTER_PLAYER, 0);
        $this->setGameStateInitialValue(self::GV_PENDING_HUNTER_TARGET, 0);
        $this->setGameStateInitialValue(self::GV_PENDING_HUNTER_LIFETYPE, 0);
        $this->setGameStateInitialValue(self::GV_ACTIVE_TURN_PLAYER, 0);

        // Set first player
        $this->activeNextPlayer();
        $firstPlayerId = (int)$this->getActivePlayerId();
        $this->setGameStateInitialValue(self::GV_FIRST_PLAYER, $firstPlayerId);

        return MulliganPhase::class;
    }

    /**
     * Helper: Get the next player in turn order
     */
    public function getNextPlayerId(int $currentPlayerId): int
    {
        $playerOrder = $this->getNextPlayerTable();
        return (int)$playerOrder[$currentPlayerId];
    }

    /**
     * Helper: Get all player IDs in order
     */
    public function getPlayerIds(): array
    {
        return array_map('intval', array_keys(
            $this->getCollectionFromDb("SELECT `player_id` FROM `player` ORDER BY `player_no`")
        ));
    }

    /**
     * Helper: Count cards that can be played from hand
     */
    public function getPlayableCards(int $playerId): array
    {
        $hand = $this->cards->getCardsInLocation('hand', $playerId);
        $playable = [];

        foreach ($hand as $card) {
            if (RulesEngine::canPlayCard($this, $playerId, $card)) {
                $playable[] = $card;
            }
        }

        return $playable;
    }

    /**
     * Execute catastrophe effect
     */
    public function executeCatastrophe(string $catastropheType, int $playerId): array
    {
        $removedCards = [];
        $playerIds = $this->getPlayerIds();

        // Determine which life types to remove
        $lifeTypesToRemove = [];
        if ($catastropheType === 'catastrophe_fire') {
            $lifeTypesToRemove = ['small_life', 'big_life', 'flying_life'];
        } elseif ($catastropheType === 'catastrophe_water') {
            $lifeTypesToRemove = ['aquatic_life'];
        } elseif ($catastropheType === 'catastrophe_both') {
            $lifeTypesToRemove = ['small_life', 'big_life', 'flying_life', 'aquatic_life'];
        }

        // Remove from ALL habitats (including the player who played it)
        foreach ($playerIds as $pid) {
            $playerRemoved = $this->discardLifeAndEnhancers($pid, $lifeTypesToRemove);
            foreach ($playerRemoved as $card) {
                $removedCards[] = ['card' => $card, 'player_id' => $pid];
            }
        }

        return $removedCards;
    }

    /**
     * Execute predator: remove a specific life card from target's habitat
     */
    public function executePredator(int $targetPlayerId, int $targetCardId): ?array
    {
        $card = $this->cards->getCard($targetCardId);
        if (!$card || $card['location'] !== 'habitat' || (int)$card['location_arg'] !== $targetPlayerId) {
            return null;
        }
        if (!self::isLifeType($card['type'])) {
            return null;
        }

        $this->cards->moveCard($targetCardId, 'discard');
        return $card;
    }

    /**
     * Execute hunter: remove all cards of a life type from target's habitat
     */
    public function executeHunter(int $targetPlayerId, string $lifeType): array
    {
        return $this->discardLifeAndEnhancers($targetPlayerId, [$lifeType]);
    }

    /**
     * Debug functions
     */
    public function debug_goToState(int $state = 3) {
        $this->gamestate->jumpToState($state);
    }
}
