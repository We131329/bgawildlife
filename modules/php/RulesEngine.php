<?php
declare(strict_types=1);

namespace Bga\Games\WildLife;

use Bga\GameFramework\UserException;

class RulesEngine
{
    /**
     * Check if a card can be played by a player
     */
    public static function canPlayCard(Game $game, int $playerId, array $card): bool
    {
        $cardType = $card['type'];
        $category = Game::getCardCategory($cardType);

        // Life cards, rain, aggressors, catastrophes are always playable during turn
        if (in_array($category, ['life', 'rain', 'aggressor', 'catastrophe'])) {
            return true;
        }

        // Enhancers: need at least 1 of the target life type, and no existing enhancer of same type
        if ($category === 'enhancer') {
            $targetLife = Game::ENHANCER_TARGETS[$cardType] ?? null;
            if (!$targetLife) return false;

            $habitat = $game->cards->getCardsInLocation('habitat', $playerId);
            $hasTargetLife = false;
            $hasExistingEnhancer = false;
            foreach ($habitat as $hCard) {
                if ($hCard['type'] === $targetLife) $hasTargetLife = true;
                if ($hCard['type'] === $cardType) $hasExistingEnhancer = true;
            }

            return $hasTargetLife && !$hasExistingEnhancer;
        }

        // Protectors are reactive only (played during hunter response), not during normal turn
        return false;
    }

    /**
     * Validate card play and throw UserException if invalid
     */
    public static function validatePlay(Game $game, int $playerId, array $card): void
    {
        $cardType = $card['type'];
        $category = Game::getCardCategory($cardType);

        if ($category === 'enhancer') {
            $targetLife = Game::ENHANCER_TARGETS[$cardType] ?? null;
            if (!$targetLife) throw new UserException('Invalid enhancer type');

            $habitat = $game->cards->getCardsInLocation('habitat', $playerId);
            $hasTargetLife = false;
            $hasExistingEnhancer = false;
            foreach ($habitat as $hCard) {
                if ($hCard['type'] === $targetLife) $hasTargetLife = true;
                if ($hCard['type'] === $cardType) $hasExistingEnhancer = true;
            }

            if (!$hasTargetLife) {
                throw new UserException(clienttranslate('You need at least one life card of the matching type'));
            }
            if ($hasExistingEnhancer) {
                throw new UserException(clienttranslate('You already have an enhancer for this life type'));
            }
        }
        
        // Add more complex validations here as the game grows
    }
}
