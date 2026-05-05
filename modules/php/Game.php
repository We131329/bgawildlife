<?php
/**
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * WildLife: The Card Game implementation
 * Implementation: Erickbond
 */
declare(strict_types=1);

namespace Bga\Games\WildLife;

// Explicitly include managers (BGA does not have PSR-4 autoloader by default)
require_once('Managers/CardManager.php');
require_once('Managers/PlayerManager.php');
require_once('Managers/HabitatManager.php');
require_once('Core/Notifications.php');

use Bga\Games\WildLife\States\MulliganPhase;
use Bga\Games\WildLife\Managers\CardManager;
use Bga\Games\WildLife\Managers\PlayerManager;
use Bga\Games\WildLife\Managers\HabitatManager;

class Game extends \Bga\GameFramework\Table
{
    /** @var \Bga\GameFramework\Components\Deck */
    public $cards;

    // Global variable IDs
    public const GV_CURRENT_CYCLE = 'current_cycle';
    public const GV_TOTAL_CYCLES = 'total_cycles';
    public const GV_CARDS_PLAYED_THIS_CYCLE = 'cards_played_this_cycle';
    public const GV_FIRST_PLAYER = 'first_player';
    public const GV_PENDING_HUNTER_PLAYER = 'pending_hunter_player';
    public const GV_PENDING_HUNTER_TARGET = 'pending_hunter_target';
    public const GV_PENDING_HUNTER_LIFETYPE = 'pending_hunter_lifetype';
    public const GV_ACTIVE_TURN_PLAYER = 'active_turn_player';

    public static ?Game $instance = null;

    public function __construct()
    {
        parent::__construct();
        self::$instance = $this;

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
        CardManager::init();
    }

    public static function get(): Game
    {
        return self::$instance;
    }

    // --- PROXIES TO MANAGERS (To keep existing States working) ---

    public function calculateHabitatScore(int $playerId): int
    {
        return HabitatManager::calculateScore($playerId);
    }

    public function discardLifeAndEnhancers(int $playerId, array $lifeTypesToRemove): array
    {
        return HabitatManager::discardLifeAndEnhancers($playerId, $lifeTypesToRemove);
    }

    public function executeCatastrophe(string $catastropheType, int $playerId): array
    {
        return HabitatManager::executeCatastrophe($catastropheType, $playerId);
    }

    public function getPlayerIds(): array
    {
        return PlayerManager::getIds();
    }

    public function getNextPlayerId(int $currentPlayerId): int
    {
        return PlayerManager::getNextId($currentPlayerId);
    }

    public function getPlayableCards(int $playerId): array
    {
        return PlayerManager::getPlayableCards($playerId);
    }

    public function executePredator(int $targetPlayerId, int $targetCardId): ?array
    {
        return HabitatManager::executePredator($targetPlayerId, $targetCardId);
    }

    public function executeHunter(int $targetPlayerId, string $lifeType): array
    {
        return HabitatManager::executeHunter($targetPlayerId, $lifeType);
    }

    // --- BGA FRAMEWORK METHODS ---

    public function getGameProgression()
    {
        return PlayerManager::getProgression();
    }

    public function upgradeTableDb($from_version)
    {
    }

    protected function getAllDatas(int $currentPlayerId): array
    {
        $result = [];
        $result["players"] = $this->getCollectionFromDb(
            "SELECT `player_id` AS `id`, `player_score` AS `score`, `player_name` AS `name`, `player_color` AS `color` FROM `player`"
        );

        $result["hand"] = array_values($this->cards->getCardsInLocation('hand', $currentPlayerId));
        $result["habitats"] = [];
        foreach (array_keys($result["players"]) as $pid) {
            $result["habitats"][$pid] = array_values($this->cards->getCardsInLocation('habitat', (int)$pid));
        }

        $result["deckCount"] = $this->cards->countCardInLocation('deck');
        $result["discardCount"] = $this->cards->countCardInLocation('discard');
        $result["currentCycle"] = $this->getGameStateValue(self::GV_CURRENT_CYCLE);
        $result["totalCycles"] = $this->getGameStateValue(self::GV_TOTAL_CYCLES);
        $result["cardsPlayedThisCycle"] = $this->getGameStateValue(self::GV_CARDS_PLAYED_THIS_CYCLE);
        $result["firstPlayer"] = $this->getGameStateValue(self::GV_FIRST_PLAYER);
        $result["cardTypes"] = CardManager::$TYPES;

        $result["handCounts"] = [];
        foreach (array_keys($result["players"]) as $pid) {
            $result["handCounts"][$pid] = $this->cards->countCardInLocation('hand', (int)$pid);
        }

        return $result;
    }

    protected function setupNewGame($players, $options = [])
    {
        $gameinfos = $this->getGameinfos();
        $default_colors = $gameinfos['player_colors'];

        foreach ($players as $player_id => $player) {
            $query_values[] = vsprintf("(%s, '%s', '%s')", [
                $player_id,
                array_shift($default_colors),
                addslashes($player["player_name"]),
            ]);
        }

        static::DbQuery(sprintf("INSERT INTO `player` (`player_id`, `player_color`, `player_name`) VALUES %s", implode(",", $query_values)));
        $this->reattributeColorsBasedOnPreferences($players, $gameinfos["player_colors"]);
        $this->reloadPlayersBasicInfos();

        $cards = [];
        foreach (CardManager::$TYPES as $key => $cardDef) {
            $cards[] = ['type' => $cardDef['card_type'], 'type_arg' => $cardDef['card_type_arg'], 'nbr' => 1];
        }

        $this->cards->createCards($cards, 'deck');
        $this->cards->shuffle('deck');

        foreach (array_keys($players) as $player_id) {
            $this->cards->pickCards(6, 'deck', $player_id);
        }

        $playerCount = count($players);
        $totalCycles = PlayerManager::CYCLES_BY_PLAYERS[$playerCount] ?? 5;

        $this->setGameStateInitialValue(self::GV_CURRENT_CYCLE, 1);
        $this->setGameStateInitialValue(self::GV_TOTAL_CYCLES, $totalCycles);
        $this->setGameStateInitialValue(self::GV_CARDS_PLAYED_THIS_CYCLE, 0);
        $this->setGameStateInitialValue(self::GV_PENDING_HUNTER_PLAYER, 0);
        $this->setGameStateInitialValue(self::GV_PENDING_HUNTER_TARGET, 0);
        $this->setGameStateInitialValue(self::GV_PENDING_HUNTER_LIFETYPE, 0);
        $this->setGameStateInitialValue(self::GV_ACTIVE_TURN_PLAYER, 0);

        $this->activeNextPlayer();
        $this->setGameStateInitialValue(self::GV_FIRST_PLAYER, (int)$this->getActivePlayerId());

        return MulliganPhase::class;
    }
}
