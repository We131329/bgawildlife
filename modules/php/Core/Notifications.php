<?php
/**
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * WildLife: The Card Game implementation
 * Implementation: Erickbond
 */
declare(strict_types=1);

namespace Bga\Games\WildLife\Core;

use Bga\Games\WildLife\Game;

/**
 * Notifications: Centralize all client-side notifications using the modern BGA API.
 */
class Notifications
{
    public static function log(string $msg, array $args = []): void
    {
        Game::get()->bga->notify->all('log', $msg, $args);
    }

    public static function cardPlayed(int $playerId, array $card, string $location = 'habitat'): void
    {
        Game::get()->bga->notify->all('cardPlayed', '', [
            'player_id' => $playerId,
            'card' => $card,
            'location' => $location,
        ]);
    }

    public static function cardsDiscarded(int $playerId, array $cards): void
    {
        Game::get()->bga->notify->all('cardsDiscarded', '', [
            'player_id' => $playerId,
            'cards' => $cards,
        ]);
    }

    public static function scoreUpdated(int $playerId, int $score): void
    {
        Game::get()->bga->notify->all('scoreUpdated', '', [
            'player_id' => $playerId,
            'score' => $score,
        ]);
    }
    
    public static function refreshHabitat(int $playerId, array $habitat): void
    {
        Game::get()->bga->notify->all('refreshHabitat', '', [
            'player_id' => $playerId,
            'habitat' => $habitat,
        ]);
    }

    public static function notifyPlayer(int $playerId, string $type, string $msg, array $args = []): void
    {
        Game::get()->bga->notify->player($playerId, $type, $msg, $args);
    }
}
