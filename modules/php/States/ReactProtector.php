<?php
declare(strict_types=1);

namespace Bga\Games\WildLife\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\UserException;
use Bga\Games\WildLife\Game;
use Bga\Games\WildLife\Managers\CardManager;

class ReactProtector extends GameState
{
    function __construct(
        protected Game $game,
    ) {
        parent::__construct($game,
            id: 12,
            type: StateType::ACTIVE_PLAYER,
        );
    }

    public function getArgs(): array
    {
        $activePlayerId = (int)$this->game->getActivePlayerId();
        $hand = $this->game->cards->getCardsInLocation('hand', $activePlayerId);

        $protectors = [];
        foreach ($hand as $card) {
            if ($card['type'] === 'protector') {
                $protectors[] = $card;
            }
        }

        $lifeTypeInt = (int)$this->game->getGameStateValue(Game::GV_PENDING_HUNTER_LIFETYPE);
        $hunterPlayer = (int)$this->game->getGameStateValue(Game::GV_PENDING_HUNTER_PLAYER);

        return [
            'protectors' => array_values($protectors),
            'hunterPlayerId' => $hunterPlayer,
            'hunterPlayerName' => $this->game->getPlayerNameById((int)$hunterPlayer),
            'targetedLifeType' => CardManager::ID_TO_LIFE_TYPE[$lifeTypeInt] ?? 'unknown',
        ];
    }

    /**
     * Use a protector to block the hunter
     */
    #[PossibleAction]
    public function actUseProtector(int $card_id, int $activePlayerId, array $args)
    {
        $card = $this->game->cards->getCard($card_id);
        if (!$card || $card['location'] !== 'hand' || (int)$card['location_arg'] !== $activePlayerId) {
            throw new UserException('This card is not in your hand');
        }
        if ($card['type'] !== 'protector') {
            throw new UserException('This is not a protector card');
        }

        // Discard the protector
        $this->game->cards->moveCard($card_id, 'discard');

        // Update stats
        $this->game->bga->playerStats->inc('protectors_used', 1, $activePlayerId);

        $lifeTypeInt = (int)$this->game->getGameStateValue(Game::GV_PENDING_HUNTER_LIFETYPE);
        $lifeType = CardManager::ID_TO_LIFE_TYPE[$lifeTypeInt] ?? 'unknown';

        $this->bga->notify->all("protectorUsed", clienttranslate('${player_name} uses a Protector! ${life_type_name} animals are safe!'), [
            'player_id' => $activePlayerId,
            'player_name' => $this->game->getPlayerNameById($activePlayerId),
            'protector_card' => $card,
            'life_type' => $lifeType,
            'life_type_name' => $lifeType,
        ]);

        // Clear pending hunter
        $this->game->setGameStateValue(Game::GV_PENDING_HUNTER_PLAYER, 0);
        $this->game->setGameStateValue(Game::GV_PENDING_HUNTER_TARGET, 0);
        $this->game->setGameStateValue(Game::GV_PENDING_HUNTER_LIFETYPE, 0);

        return NextPlayer::class;
    }

    /**
     * Decline to use protector - hunter effect proceeds
     */
    #[PossibleAction]
    public function actDeclineProtector(int $activePlayerId, array $args)
    {
        $targetPlayerId = $activePlayerId; // The target IS the active player in this state
        $lifeTypeInt = (int)$this->game->getGameStateValue(Game::GV_PENDING_HUNTER_LIFETYPE);
        $lifeType = CardManager::ID_TO_LIFE_TYPE[$lifeTypeInt] ?? 'unknown';

        // Execute hunter effect
        $removedCards = $this->game->executeHunter($targetPlayerId, $lifeType);

        $hunterPlayerId = (int)$this->game->getGameStateValue(Game::GV_PENDING_HUNTER_PLAYER);
        $hunterPlayerName = $this->game->getPlayerNameById($hunterPlayerId);

        $this->bga->notify->all("hunterResolved", clienttranslate('${hunter_name}\'s Hunter removes ${nbr} ${life_type} card(s) from ${target_name}!'), [
            'target_id' => $targetPlayerId,
            'target_name' => $this->game->getPlayerNameById($targetPlayerId),
            'hunter_name' => $hunterPlayerName,
            'removed_cards' => $removedCards,
            'nbr' => count($removedCards),
            'life_type' => $lifeType,
        ]);

        // Check if removal orphaned an enhancer
        $habitat = $this->game->cards->getCardsInLocation('habitat', $targetPlayerId);
        $lifeTypesRemaining = [];
        $enhancers = [];
        foreach ($habitat as $card) {
            if (CardManager::isLife($card['type'])) $lifeTypesRemaining[$card['type']] = true;
            if (str_starts_with($card['type'], 'enhancer_')) $enhancers[] = $card;
        }

        $orphaned = [];
        foreach ($enhancers as $enhancer) {
            $target = CardManager::ENHANCER_TARGETS[$enhancer['type']] ?? null;
            if ($target && !isset($lifeTypesRemaining[$target])) {
                $this->game->cards->moveCard((int)$enhancer['id'], 'discard');
                $orphaned[] = $enhancer;
            }
        }

        if (!empty($orphaned)) {
            $this->bga->notify->all("enhancerLost", clienttranslate('${target_name} loses an Enhancer because no animals of that type remain!'), [
                'target_id' => $targetPlayerId,
                'target_name' => $this->game->getPlayerNameById($targetPlayerId),
                'removed_cards' => $orphaned,
                'nbr' => count($orphaned),
            ]);
        }

        // Clear pending hunter
        $this->game->setGameStateValue(Game::GV_PENDING_HUNTER_PLAYER, 0);
        $this->game->setGameStateValue(Game::GV_PENDING_HUNTER_TARGET, 0);
        $this->game->setGameStateValue(Game::GV_PENDING_HUNTER_LIFETYPE, 0);

        return NextPlayer::class;
    }

    function zombie(int $playerId) {
        $args = $this->getArgs();
        return $this->actDeclineProtector($playerId, $args);
    }
}
