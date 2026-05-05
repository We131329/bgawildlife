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

const ST_END_GAME = 99;

class EndScore extends \Bga\GameFramework\States\GameState
{
    function __construct(
        protected Game $game,
    ) {
        parent::__construct($game,
            id: 98,
            type: StateType::GAME,
        );
    }

    public function onEnteringState() {
        // Compute tiebreaker: player_score_aux = encoded best cycle scores
        $playerIds = $this->game->getPlayerIds();

        // Get all cycle scores
        $cycleScores = \Bga\GameFramework\Table::getCollectionFromDB(
            "SELECT `player_id`, `cycle_num`, `score` FROM `cycle_score` ORDER BY `cycle_num` DESC"
        );

        // Group by player
        $byPlayer = [];
        foreach ($cycleScores as $row) {
            $pid = $row['player_id'];
            if (!isset($byPlayer[$pid])) $byPlayer[$pid] = [];
            $byPlayer[$pid][] = (int)$row['score'];
        }

        // Compute aux score: encode up to 4 last cycle scores into a 32-bit signed integer
        // Using a base-128 (7 bits) multiplier allows fitting 4 cycles (28 bits) safely under 2^31.
        foreach ($playerIds as $pid) {
            $scores = $byPlayer[$pid] ?? [];
            $aux = 0;
            $multiplier = 1;
            // Scores are DESC (last cycle first), so the most recent cycles have the highest weights.
            $count = 0;
            foreach ($scores as $s) {
                // Limit each cycle score to 127 for the encoding
                $val = min(127, $s);
                $aux += $val * $multiplier;
                $multiplier *= 128;
                
                $count++;
                if ($count >= 4) break; // Max 4 cycles to fit in 32-bit INT
            }
            \Bga\GameFramework\Table::DbQuery("UPDATE `player` SET `player_score_aux` = {$aux} WHERE `player_id` = {$pid}");
        }

        $this->bga->notify->all("gameEnd", clienttranslate('Game over! Final scores are tallied.'), []);

        return ST_END_GAME;
    }
}
