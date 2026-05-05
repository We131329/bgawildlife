<?php
/**
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * WildLife: The Card Game implementation
 * Implementation: Erickbond
 */
declare(strict_types=1);

namespace Bga\Games\WildLife\Managers;

use Bga\Games\WildLife\Game;

/**
 * Players Manager: Handles player queries, turn order, and progression.
 */
class Players
{
    /**
     * Cycles per player count
     */
    public const CYCLES_BY_PLAYERS = [
        2 => 8,
        3 => 6,
        4 => 4,
        5 => 5,
    ];

    public static function getIds(): array
    {
        return array_map('intval', array_keys(
            Game::get()->getCollectionFromDb("SELECT `player_id` FROM `player` ORDER BY `player_no`")
        ));
    }

    public static function getNextId(int $currentPlayerId): int
    {
        $playerOrder = Game::get()->getNextPlayerTable();
        return (int)$playerOrder[$currentPlayerId];
    }

    public static function getProgression(): int
    {
        $currentCycle = (int)Game::get()->getGameStateValue(Game::GV_CURRENT_CYCLE);
        $totalCycles = (int)Game::get()->getGameStateValue(Game::GV_TOTAL_CYCLES);
        $firstPlayerId = (int)Game::get()->getGameStateValue(Game::GV_FIRST_PLAYER);
        
        $playerIds = self::getIds();
        $playerCount = count($playerIds);
        
        if ($totalCycles <= 0 || $playerCount <= 0) return 0;

        $activePlayerId = (int)Game::get()->getActivePlayerId();
        
        $firstPlayerIdx = array_search($firstPlayerId, $playerIds);
        $activePlayerIdx = array_search($activePlayerId, $playerIds);
        
        if ($firstPlayerIdx === false || $activePlayerIdx === false) {
            return min(100, (int)(($currentCycle - 1) * 100 / $totalCycles));
        }

        $playersCompletedInCycle = ($activePlayerIdx - $firstPlayerIdx + $playerCount) % $playerCount;
        
        $totalTurns = $totalCycles * $playerCount;
        $completedTurns = ($currentCycle - 1) * $playerCount + $playersCompletedInCycle;
        
        return min(100, (int)($completedTurns * 100 / $totalTurns));
    }
}
