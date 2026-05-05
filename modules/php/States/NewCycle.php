<?php
/**
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * WildLife: The Card Game implementation
 * Implementation: Erickbond
 */
declare(strict_types=1);

namespace Bga\Games\WildLife\States;

use Bga\GameFramework\StateType;
use Bga\Games\WildLife\Game;

class NewCycle extends \Bga\GameFramework\States\GameState
{
    function __construct(
        protected Game $game,
    ) {
        parent::__construct($game,
            id: 20,
            type: StateType::GAME,
        );
    }

    function onEnteringState() {
        $currentCycle = (int)$this->game->getGameStateValue(Game::GV_CURRENT_CYCLE);
        $totalCycles = (int)$this->game->getGameStateValue(Game::GV_TOTAL_CYCLES);

        // Rotate first player (after cycle 1, pass to the left)
        if ($currentCycle > 1) {
            $firstPlayer = (int)$this->game->getGameStateValue(Game::GV_FIRST_PLAYER);
            $nextFirstPlayer = $this->game->getNextPlayerId($firstPlayer);
            $this->game->setGameStateValue(Game::GV_FIRST_PLAYER, $nextFirstPlayer);
        }

        // Reset cards played counter
        $this->game->setGameStateValue(Game::GV_CARDS_PLAYED_THIS_CYCLE, 0);

        $firstPlayer = (int)$this->game->getGameStateValue(Game::GV_FIRST_PLAYER);

        // Notify all players about the new cycle
        $this->bga->notify->all("newCycle", clienttranslate('Cycle ${cycle_num} of ${total_cycles} begins'), [
            'cycle_num' => $currentCycle,
            'total_cycles' => $totalCycles,
            'first_player' => $firstPlayer,
        ]);

        return DrawPhase::class;
    }
}
