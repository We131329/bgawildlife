<?php
declare(strict_types=1);

namespace Bga\Games\WildLife\Core;

use Bga\Games\WildLife\Game;

/**
 * Notifications: Centralize all client-side notifications.
 */
class Notifications
{
    public static function log(string $msg, array $args = []): void
    {
        Game::get()->notifyAllPlayers('log', $msg, $args);
    }

    public static function cardPlayed(int $playerId, array $card, string $location = 'habitat'): void
    {
        Game::get()->notifyAllPlayers('cardPlayed', '', [
            'player_id' => $playerId,
            'card' => $card,
            'location' => $location,
        ]);
    }

    public static function cardsDiscarded(int $playerId, array $cards): void
    {
        Game::get()->notifyAllPlayers('cardsDiscarded', '', [
            'player_id' => $playerId,
            'cards' => $cards,
        ]);
    }

    public static function scoreUpdated(int $playerId, int $score): void
    {
        Game::get()->notifyAllPlayers('scoreUpdated', '', [
            'player_id' => $playerId,
            'score' => $score,
        ]);
    }
    
    public static function refreshHabitat(int $playerId, array $habitat): void
    {
        Game::get()->notifyAllPlayers('refreshHabitat', '', [
            'player_id' => $playerId,
            'habitat' => $habitat,
        ]);
    }
}
