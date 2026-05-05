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
 * Habitat Manager: Handles habitat logic, scoring, and card removals.
 */
class Habitat
{
    /**
     * Discard life cards and their associated enhancers from a player's habitat.
     */
    public static function discardLifeAndEnhancers(int $playerId, array $lifeTypesToRemove): array
    {
        $removedCards = [];
        $habitat = Game::get()->cards->getCardsInLocation('habitat', $playerId);

        foreach ($habitat as $card) {
            $ct = $card['type'];
            $shouldRemove = false;

            if (in_array($ct, $lifeTypesToRemove)) {
                $shouldRemove = true;
            }

            if (str_starts_with($ct, 'enhancer_')) {
                $targetLife = Cards::ENHANCER_TARGETS[$ct] ?? null;
                if ($targetLife && in_array($targetLife, $lifeTypesToRemove)) {
                    $shouldRemove = true;
                }
            }

            if ($shouldRemove) {
                Game::get()->cards->moveCard((int)$card['id'], 'discard');
                $removedCards[] = $card;
            }
        }

        return $removedCards;
    }

    /**
     * Calculate score for a player's habitat.
     */
    public static function calculateScore(int $playerId): int
    {
        $habitatCards = Game::get()->cards->getCardsInLocation('habitat', $playerId);
        $score = 0;

        $byType = [];
        foreach ($habitatCards as $card) {
            $type = $card['type'];
            if (!isset($byType[$type])) $byType[$type] = [];
            $byType[$type][] = $card;
        }

        $hasEnhancer = [];
        foreach (Cards::ENHANCER_TARGETS as $enhType => $lifeType) {
            $hasEnhancer[$lifeType] = isset($byType[$enhType]) && count($byType[$enhType]) > 0;
        }

        // Score small life
        $smallCount = count($byType['small_life'] ?? []);
        $smallPoints = $smallCount * 1;
        if ($hasEnhancer['small_life']) $smallPoints *= Cards::ENHANCER_MULTIPLIERS['enhancer_spring'];
        $score += $smallPoints;

        // Score big life
        $bigCount = count($byType['big_life'] ?? []);
        $bigPoints = $bigCount * 2;
        if ($hasEnhancer['big_life']) $bigPoints *= Cards::ENHANCER_MULTIPLIERS['enhancer_winter'];
        $score += $bigPoints;

        // Score flying life
        $flyingCards = $byType['flying_life'] ?? [];
        $flyingPoints = 0;
        foreach ($flyingCards as $card) {
            $info = Cards::getInfo($card['type'], (int)$card['type_arg']);
            $flyingPoints += $info['points'] ?? 1;
        }
        if ($hasEnhancer['flying_life']) $flyingPoints *= Cards::ENHANCER_MULTIPLIERS['enhancer_nesting'];
        $score += $flyingPoints;

        // Score aquatic life
        $aquaticCount = count($byType['aquatic_life'] ?? []);
        $aquaticPoints = Cards::AQUATIC_SCORING[min($aquaticCount, 5)] ?? 0;
        if ($hasEnhancer['aquatic_life']) $aquaticPoints *= Cards::ENHANCER_MULTIPLIERS['enhancer_spawning'];
        $score += $aquaticPoints;

        // Score rain
        $rainCount = count($byType['rain'] ?? []);
        $score += $rainCount * 3;

        return (int)$score;
    }

    /**
     * Execute catastrophe effect on ALL players.
     */
    public static function executeCatastrophe(string $catastropheType, int $playedByPlayerId): array
    {
        $removedCards = [];
        $playerIds = Players::getIds();

        $lifeTypesToRemove = [];
        if ($catastropheType === 'catastrophe_fire') {
            $lifeTypesToRemove = ['small_life', 'big_life', 'flying_life'];
        } elseif ($catastropheType === 'catastrophe_water') {
            $lifeTypesToRemove = ['aquatic_life'];
        } elseif ($catastropheType === 'catastrophe_both') {
            $lifeTypesToRemove = ['small_life', 'big_life', 'flying_life', 'aquatic_life'];
        }

        foreach ($playerIds as $pid) {
            $playerRemoved = self::discardLifeAndEnhancers($pid, $lifeTypesToRemove);
            foreach ($playerRemoved as $card) {
                $removedCards[] = ['card' => $card, 'player_id' => $pid];
            }
        }

        return $removedCards;
    }
}
