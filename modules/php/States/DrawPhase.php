<?php
declare(strict_types=1);

namespace Bga\Games\WildLife\States;

use Bga\GameFramework\StateType;
use Bga\Games\WildLife\Game;

class DrawPhase extends \Bga\GameFramework\States\GameState
{
    function __construct(
        protected Game $game,
    ) {
        parent::__construct($game,
            id: 21,
            type: StateType::GAME,
        );
    }

    function onEnteringState() {
        $playerIds = $this->game->getPlayerIds();

        // Each player draws until they have 6 cards
        foreach ($playerIds as $pid) {
            $handCount = $this->game->cards->countCardInLocation('hand', $pid);
            $toDraw = 6 - $handCount;

            if ($toDraw > 0) {
                $deckCount = $this->game->cards->countCardInLocation('deck');
                $actualDraw = min($toDraw, $deckCount);

                if ($actualDraw > 0) {
                    $drawnCards = $this->game->cards->pickCards($actualDraw, 'deck', $pid);

                    // Notify the player about their drawn cards (private)
                    $this->bga->notify->player($pid, "cardsDrawn", clienttranslate('You draw ${nbr} card(s)'), [
                        'cards' => array_values($drawnCards),
                        'nbr' => $actualDraw,
                    ]);

                    // Notify all players about the draw count (public)
                    $this->bga->notify->all("playerDrew", clienttranslate('${player_name} draws ${nbr} card(s)'), [
                        'player_id' => $pid,
                        'player_name' => $this->game->getPlayerNameById($pid),
                        'nbr' => $actualDraw,
                        'handCount' => $handCount + $actualDraw,
                        'deckCount' => $this->game->cards->countCardInLocation('deck'),
                    ]);
                }
            }
        }

        // Set the first player as active and start the habitat phase
        $firstPlayer = (int)$this->game->getGameStateValue(Game::GV_FIRST_PLAYER);
        $this->game->gamestate->changeActivePlayer($firstPlayer);
        $this->game->giveExtraTime($firstPlayer);
        $this->game->setGameStateValue(Game::GV_CARDS_PLAYED_THIS_CYCLE, 0);
        $this->game->setGameStateValue(Game::GV_ACTIVE_TURN_PLAYER, $firstPlayer);

        return PlayerTurn::class;
    }
}
