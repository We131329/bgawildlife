<?php
declare(strict_types=1);

namespace Bga\Games\WildLife\States;

use Bga\GameFramework\StateType;
use Bga\Games\WildLife\Game;

class NextPlayer extends \Bga\GameFramework\States\GameState
{
    function __construct(
        protected Game $game,
    ) {
        parent::__construct($game,
            id: 90,
            type: StateType::GAME,
            updateGameProgression: true,
        );
    }

    function onEnteringState() {
        $pendingHunterPlayer = (int)$this->game->getGameStateValue(Game::GV_PENDING_HUNTER_PLAYER);

        // Dispatcher Logic: Handle Hunter reactions before continuing the turn
        if ($pendingHunterPlayer > 0) {
            $targetId = (int)$this->game->getGameStateValue(Game::GV_PENDING_HUNTER_TARGET);
            $this->gamestate->changeActivePlayer($targetId);
            return ReactProtector::class;
        }

        $cardsPlayed = (int)$this->game->getGameStateValue(Game::GV_CARDS_PLAYED_THIS_CYCLE);
        $activeTurnPlayer = (int)$this->game->getGameStateValue(Game::GV_ACTIVE_TURN_PLAYER);

        // If current player hasn't played 3 cards yet, continue their turn
        if ($cardsPlayed < 3) {
            $this->game->giveExtraTime($activeTurnPlayer);
            $this->game->gamestate->changeActivePlayer($activeTurnPlayer);
            return PlayerTurn::class;
        }

        // Current player finished their 3 cards, move to next player
        $this->game->setGameStateValue(Game::GV_CARDS_PLAYED_THIS_CYCLE, 0);

        $firstPlayer = (int)$this->game->getGameStateValue(Game::GV_FIRST_PLAYER);
        $nextPlayer = $this->game->getNextPlayerId($activeTurnPlayer);

        // Check if we've gone around back to the first player (all players done)
        if ($nextPlayer === $firstPlayer) {
            // All players have played - end the cycle
            return EndCycle::class;
        }

        // Activate next player
        $this->game->setGameStateValue(Game::GV_ACTIVE_TURN_PLAYER, $nextPlayer);
        $this->game->gamestate->changeActivePlayer($nextPlayer);
        $this->game->giveExtraTime($nextPlayer);

        return PlayerTurn::class;
    }
}
