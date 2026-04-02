<?php
declare(strict_types=1);

namespace Bga\Games\WildLife\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\UserException;
use Bga\Games\WildLife\Game;

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
            'targetedLifeType' => Game::ID_TO_LIFE_TYPE[$lifeTypeInt] ?? 'unknown',
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

        $lifeTypeInt = (int)$this->game->getGameStateValue(Game::GV_PENDING_HUNTER_LIFETYPE);
        $lifeType = Game::ID_TO_LIFE_TYPE[$lifeTypeInt] ?? 'unknown';

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
        $lifeType = Game::ID_TO_LIFE_TYPE[$lifeTypeInt] ?? 'unknown';

        // Execute hunter effect
        $removedCards = $this->game->executeHunter($targetPlayerId, $lifeType);

        $this->bga->notify->all("hunterResolved", clienttranslate('${target_name} does not protect. ${nbr} card(s) removed!'), [
            'target_id' => $targetPlayerId,
            'target_name' => $this->game->getPlayerNameById($targetPlayerId),
            'removed_cards' => $removedCards,
            'nbr' => count($removedCards),
            'life_type' => $lifeType,
        ]);

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
