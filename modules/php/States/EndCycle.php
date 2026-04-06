<?php
declare(strict_types=1);

namespace Bga\Games\WildLife\States;

use Bga\GameFramework\StateType;
use Bga\Games\WildLife\Game;

class EndCycle extends \Bga\GameFramework\States\GameState
{
    function __construct(
        protected Game $game,
    ) {
        parent::__construct($game,
            id: 30,
            type: StateType::GAME,
        );
    }

    function onEnteringState() {
        $currentCycle = (int)$this->game->getGameStateValue(Game::GV_CURRENT_CYCLE);
        $totalCycles = (int)$this->game->getGameStateValue(Game::GV_TOTAL_CYCLES);
        $playerIds = $this->game->getPlayerIds();

        // Score each player's habitat
        $cycleScores = [];
        foreach ($playerIds as $pid) {
            $score = $this->game->calculateHabitatScore($pid);
            $cycleScores[$pid] = $score;

            // Add to player's total score
            $this->bga->playerScore->inc($pid, $score);

            // Update stats (Highest Cycle Score)
            $oldMax = $this->game->bga->playerStats->get('highest_cycle_score', $pid);
            if ($score > $oldMax) {
                $this->game->bga->playerStats->set('highest_cycle_score', (int)$score, $pid);
            }

            // Store cycle score for tiebreaker
            \Bga\GameFramework\Table::DbQuery(
                "INSERT INTO `cycle_score` (`player_id`, `cycle_num`, `score`) VALUES ({$pid}, {$currentCycle}, {$score})"
            );
        }

        // Update table stats
        $this->game->bga->tableStats->inc('cycles_played', 1);

        // Notify scoring
        $this->bga->notify->all("cycleScored", clienttranslate('Cycle ${cycle_num} scoring complete!'), [
            'cycle_num' => $currentCycle,
            'scores' => $cycleScores,
            'playerScores' => $this->getPlayerScores(),
        ]);

        // Remove temporary (Rain) and orphaned (Enhancer) cards from all habitats
        $cardsRemoved = [];
        foreach ($playerIds as $pid) {
            $habitat = $this->game->cards->getCardsInLocation('habitat', $pid);

            // Track which life types are present in this habitat
            $lifeTypesPresent = [];
            foreach ($habitat as $card) {
                if (Game::isLifeType($card['type'])) {
                    $lifeTypesPresent[$card['type']] = true;
                }
            }

            foreach ($habitat as $card) {
                $isRain = ($card['type'] === 'rain');
                $isOrphanedEnhancer = (str_starts_with($card['type'], 'enhancer_') && !isset($lifeTypesPresent[Game::ENHANCER_TARGETS[$card['type']] ?? '']));

                if ($isRain || $isOrphanedEnhancer) {
                    $this->game->cards->moveCard((int)$card['id'], 'discard');
                    $cardsRemoved[] = ['card' => $card, 'player_id' => $pid];
                }
            }
        }

        if (!empty($cardsRemoved)) {
            $this->bga->notify->all("rainDiscarded", clienttranslate('Temporary and orphaned cards are removed from habitats'), [
                'removed' => $cardsRemoved,
            ]);
        }

        // Check if game is over
        if ($currentCycle >= $totalCycles) {
            return EndScore::class;
        }

        // Start next cycle
        $this->game->setGameStateValue(Game::GV_CURRENT_CYCLE, $currentCycle + 1);

        return NewCycle::class;
    }

    private function getPlayerScores(): array
    {
        $scores = [];
        $result = \Bga\GameFramework\Table::getCollectionFromDB("SELECT `player_id`, `player_score` FROM `player` pip");
        foreach ($result as $row) {
            $scores[$row['player_id']] = (int)$row['player_score'];
        }
        return $scores;
    }
}
