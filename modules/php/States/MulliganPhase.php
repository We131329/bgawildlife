<?php
/**
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * WildLife: The Card Game implementation
 * Implementation: Erickbond
 */
declare(strict_types=1);

namespace Bga\Games\WildLife\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\Actions\Types\IntArrayParam;
use Bga\Games\WildLife\Game;

class MulliganPhase extends \Bga\GameFramework\States\GameState
{
    function __construct(
        protected Game $game,
    ) {
        parent::__construct($game,
            id: 5,
            type: StateType::MULTIPLE_ACTIVE_PLAYER,
            transitions: [
                'next' => \Bga\Games\WildLife\States\NewCycle::class,
            ]
        );
    }

    public function onEnteringState() {
        // Reset mulligan status for all players
        \Bga\GameFramework\Table::DbQuery("UPDATE `player` SET `player_mulligan_status` = 0");

        // Activate all players for multi-active state
        $this->gamestate->setAllPlayersMultiactive();
    }

    public function getArgs(): array {
        $firstPlayerId = (int)$this->game->getGameStateValue(Game::GV_FIRST_PLAYER);
        
        $players = $this->game->loadPlayersBasicInfos();
        $status = \Bga\GameFramework\Table::getCollectionFromDB(
            "SELECT `player_id`, `player_mulligan_status` FROM `player`",
            true
        );

        return [
            'firstPlayerId' => $firstPlayerId,
            'mulliganStatus' => $status,
        ];
    }

    /**
     * Handle zombie player: automatically accept the hand to prevent game from getting stuck
     */
    public function zombie(int $playerId): void
    {
        $this->actAcceptHand($playerId);
    }

    /**
     * Mulligan action: discard and draw new cards
     */
    #[PossibleAction]
    public function actMulligan(#[IntArrayParam] array $cardIds): void
    {
        $playerId = (int)$this->game->getCurrentPlayerId();
        $this->game->checkAction('actMulligan');

        $firstPlayerId = (int)$this->game->getGameStateValue(Game::GV_FIRST_PLAYER);
        $hand = $this->game->cards->getCardsInLocation('hand', $playerId);
        $handIds = array_keys($hand);

        if ($playerId === $firstPlayerId) {
            // First player: 1-6 cards
            if (empty($cardIds) || count($cardIds) > 6) {
                throw new \Bga\GameFramework\UserException(clienttranslate("You must select between 1 and 6 cards to mulligan"));
            }
            foreach ($cardIds as $id) {
                if (!isset($hand[$id])) throw new \Bga\GameFramework\UserException("Card not in hand");
            }
        } else {
            // Others: must be all 6 cards
            if (count($cardIds) !== 6) {
                throw new \Bga\GameFramework\UserException(clienttranslate("You must mulligan all your cards or none"));
            }
            // Ensure selected cards are exactly the player's hand
            $diff = array_diff($handIds, $cardIds);
            if (!empty($diff)) throw new \Bga\GameFramework\UserException("Card not in hand");
        }

        // Execute Mulligan: move to discard and pick new
        $count = count($cardIds);
        $this->game->cards->moveCards($cardIds, 'discard');
        $this->game->cards->pickCards($count, 'deck', $playerId);

        // Update status to "Accepted" (1) because you only get 1 mulligan
        \Bga\GameFramework\Table::DbQuery("UPDATE `player` SET `player_mulligan_status` = 1 WHERE `player_id` = {$playerId}");
        
        $this->gamestate->setPlayerNonMultiactive($playerId, 'next');

        $this->game->bga->notify->player($playerId, "mulliganResult", "", [
            'hand' => array_values($this->game->cards->getCardsInLocation('hand', $playerId))
        ]);

        $this->game->bga->notify->all("mulliganLogged", clienttranslate('${player_name} performs a mulligan (${count} cards)'), [
            'player_id' => $playerId,
            'player_name' => $this->game->getPlayerNameById($playerId),
            'count' => $count,
        ]);
    }

    /**
     * Accept hand action: player is ready
     */
    #[PossibleAction]
    public function actAcceptHand(?int $playerId = null): void
    {
        $playerId = $playerId ?? (int)$this->game->getCurrentPlayerId();
        if ($playerId === 0) return;

        // Only check permission if this is a live action from current player
        if (!$this->game->isCurrentPlayerZombie()) {
            $this->game->checkAction('actAcceptHand');
        }

        \Bga\GameFramework\Table::DbQuery("UPDATE `player` SET `player_mulligan_status` = 1 WHERE `player_id` = {$playerId}");
        $this->gamestate->setPlayerNonMultiactive($playerId, 'next');

        $this->game->bga->notify->all("acceptHandLogged", clienttranslate('${player_name} accepts their hand'), [
            'player_id' => $playerId,
            'player_name' => $this->game->getPlayerNameById($playerId),
        ]);
    }
}
