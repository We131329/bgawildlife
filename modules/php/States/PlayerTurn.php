<?php
declare(strict_types=1);

namespace Bga\Games\WildLife\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\UserException;
use Bga\Games\WildLife\Game;

class PlayerTurn extends GameState
{
    function __construct(
        protected Game $game,
    ) {
        parent::__construct($game,
            id: 10,
            type: StateType::ACTIVE_PLAYER,
        );
    }

    public function getArgs(): array
    {
        $activePlayerId = (int)$this->game->getActivePlayerId();
        $playableCards = $this->game->getPlayableCards($activePlayerId);
        $cardsPlayed = (int)$this->game->getGameStateValue(Game::GV_CARDS_PLAYED_THIS_CYCLE);
        $handCount = $this->game->cards->countCardInLocation('hand', $activePlayerId);

        // If player can't play any cards and hasn't played 3 yet, they MUST discard down to 3
        $mustDiscard = empty($playableCards) && $cardsPlayed < 3 && $handCount > 3;

        return [
            'playableCards' => array_values($playableCards),
            'playableCardIds' => array_map(fn($c) => (int)$c['id'], $playableCards),
            'cardsPlayed' => $cardsPlayed,
            'cardsRemaining' => 3 - $cardsPlayed,
            'mustDiscard' => $mustDiscard,
            'canDiscard' => $cardsPlayed < 3, // Can always choose to discard instead of playing
            'otherPlayers' => $this->getOtherPlayersWithHabitats($activePlayerId),
        ];
    }

    private function getOtherPlayersWithHabitats(int $excludePlayerId): array
    {
        $players = \Bga\GameFramework\Table::getCollectionFromDB(
            "SELECT `player_id` AS `id`, `player_name` AS `name` FROM `player` WHERE `player_id` != {$excludePlayerId}"
        );

        $result = [];
        foreach ($players as $pid => $pdata) {
            $habitat = $this->game->cards->getCardsInLocation('habitat', (int)$pid);
            $lifeCards = array_filter($habitat, fn($c) => Game::isLifeType($c['type']));
            $result[$pid] = [
                'id' => $pid,
                'name' => $pdata['name'],
                'habitatCards' => array_values($lifeCards),
                'hasLifeCards' => !empty($lifeCards),
            ];
        }
        return $result;
    }

    /**
     * Play a life card to own habitat
     */
    #[PossibleAction]
    public function actPlayLifeCard(int $card_id, int $activePlayerId, array $args)
    {
        $card = $this->game->cards->getCard($card_id);
        $this->validateCardInHand($card, $activePlayerId, $args);

        if (!Game::isLifeType($card['type'])) {
            throw new UserException('This is not a life card');
        }

        // Move card to habitat
        $this->game->cards->moveCard($card_id, 'habitat', $activePlayerId);

        $info = Game::getCardTypeInfo($card['type'], (int)$card['type_arg']);
        $this->bga->notify->all("cardPlayedToHabitat", clienttranslate('${player_name} plays ${card_name} to their habitat'), [
            'player_id' => $activePlayerId,
            'player_name' => $this->game->getPlayerNameById($activePlayerId),
            'card_name' => $info['name'] ?? $card['type'],
            'card' => $card,
            'card_id' => $card_id,
        ]);

        // Check if removal orphaned an enhancer
        $orphaned = $this->cleanupOrphanedEnhancers($activePlayerId);
        if (!empty($orphaned)) {
            $this->bga->notify->all("hunterResolved", clienttranslate('${target_name} loses an Enhancer because no animals of that type remain!'), [
                'target_id' => $activePlayerId,
                'target_name' => $this->game->getPlayerNameById($activePlayerId),
                'removed_cards' => $orphaned,
                'nbr' => count($orphaned),
            ]);
        }

        return $this->afterCardPlayed($activePlayerId);
    }

    /**
     * Play an enhancer card
     */
    #[PossibleAction]
    public function actPlayEnhancer(int $card_id, int $activePlayerId, array $args)
    {
        $card = $this->game->cards->getCard($card_id);
        $this->validateCardInHand($card, $activePlayerId, $args);

        $cardType = $card['type'];
        if (!str_starts_with($cardType, 'enhancer_')) {
            throw new UserException('This is not an enhancer card');
        }

        // Verify rules
        \Bga\Games\WildLife\RulesEngine::validatePlay($this->game, $activePlayerId, $card);

        $this->game->cards->moveCard($card_id, 'habitat', $activePlayerId);

        $info = Game::getCardTypeInfo($card['type'], (int)$card['type_arg']);
        $this->bga->notify->all("cardPlayedToHabitat", clienttranslate('${player_name} plays ${card_name} enhancer'), [
            'player_id' => $activePlayerId,
            'player_name' => $this->game->getPlayerNameById($activePlayerId),
            'card_name' => $info['name'] ?? $cardType,
            'card' => $card,
            'card_id' => $card_id,
        ]);

        return $this->afterCardPlayed($activePlayerId);
    }

    /**
     * Play a rain card
     */
    #[PossibleAction]
    public function actPlayRain(int $card_id, int $activePlayerId, array $args)
    {
        $card = $this->game->cards->getCard($card_id);
        $this->validateCardInHand($card, $activePlayerId, $args);

        if ($card['type'] !== 'rain') {
            throw new UserException('This is not a rain card');
        }

        $this->game->cards->moveCard($card_id, 'habitat', $activePlayerId);

        $this->bga->notify->all("cardPlayedToHabitat", clienttranslate('${player_name} plays Rain (3 points this cycle)'), [
            'player_id' => $activePlayerId,
            'player_name' => $this->game->getPlayerNameById($activePlayerId),
            'card_name' => _('Rain'),
            'card' => $card,
            'card_id' => $card_id,
        ]);

        return $this->afterCardPlayed($activePlayerId);
    }

    /**
     * Play a predator card against an opponent
     */
    #[PossibleAction]
    public function actPlayPredator(int $card_id, int $target_player_id, int $target_card_id, int $activePlayerId, array $args)
    {
        $card = $this->game->cards->getCard($card_id);
        $this->validateCardInHand($card, $activePlayerId, $args);

        if ($card['type'] !== 'predator') {
            throw new UserException('This is not a predator card');
        }
        if ($target_player_id === $activePlayerId) {
            throw new UserException('You cannot target yourself');
        }

        // Execute predator effect
        $removedCard = $this->game->executePredator($target_player_id, $target_card_id);
        if (!$removedCard) {
            throw new UserException('Invalid target card');
        }

        // Discard the predator card
        $this->game->cards->moveCard($card_id, 'discard');

        // Check if removal orphaned an enhancer for the target player
        $orphaned = $this->cleanupOrphanedEnhancers($target_player_id);
        if (!empty($orphaned)) {
            $this->bga->notify->all("hunterResolved", clienttranslate('${target_name} loses an Enhancer because no animals of that type remain!'), [
                'target_id' => $target_player_id,
                'target_name' => $this->game->getPlayerNameById($target_player_id),
                'removed_cards' => $orphaned,
                'nbr' => count($orphaned),
            ]);
        }

        $removedInfo = Game::getCardTypeInfo($removedCard['type'], (int)$removedCard['type_arg']);
        $this->bga->notify->all("predatorPlayed", clienttranslate('${player_name} plays a Predator! ${target_name} loses ${card_name}'), [
            'player_id' => $activePlayerId,
            'player_name' => $this->game->getPlayerNameById($activePlayerId),
            'target_id' => $target_player_id,
            'target_name' => $this->game->getPlayerNameById($target_player_id),
            'card_name' => $removedInfo['name'] ?? $removedCard['type'],
            'predator_card' => $card,
            'removed_card' => $removedCard,
        ]);

        return $this->afterCardPlayed($activePlayerId);
    }

    /**
     * Play a hunter card - targets all of one life type from an opponent
     */
    #[PossibleAction]
    public function actPlayHunter(int $card_id, int $target_player_id, string $life_type, int $activePlayerId, array $args)
    {
        $card = $this->game->cards->getCard($card_id);
        $this->validateCardInHand($card, $activePlayerId, $args);

        if ($card['type'] !== 'hunter') {
            throw new UserException('This is not a hunter card');
        }
        if ($target_player_id === $activePlayerId) {
            throw new UserException('You cannot target yourself');
        }
        if (!Game::isLifeType($life_type)) {
            throw new UserException('Invalid life type');
        }

        // Store pending hunter info for protector reaction
        $this->game->setGameStateValue(Game::GV_PENDING_HUNTER_PLAYER, $activePlayerId);
        $this->game->setGameStateValue(Game::GV_PENDING_HUNTER_TARGET, $target_player_id);

        // Encode life type as int for storage
        $this->game->setGameStateValue(Game::GV_PENDING_HUNTER_LIFETYPE, Game::LIFE_TYPE_TO_ID[$life_type] ?? 0);

        // Discard the hunter card
        $this->game->cards->moveCard($card_id, 'discard');

        // Increment cards played
        $played = (int)$this->game->getGameStateValue(Game::GV_CARDS_PLAYED_THIS_CYCLE) + 1;
        $this->game->setGameStateValue(Game::GV_CARDS_PLAYED_THIS_CYCLE, $played);

        $this->bga->notify->all("hunterPlayed", clienttranslate('${player_name} plays a Hunter against ${target_name}, targeting ${life_type}!'), [
            'player_id' => $activePlayerId,
            'player_name' => $this->game->getPlayerNameById($activePlayerId),
            'target_id' => $target_player_id,
            'target_name' => $this->game->getPlayerNameById($target_player_id),
            'life_type' => $life_type,
            'hunter_card' => $card,
        ]);

        return NextPlayer::class;
    }

    /**
     * Play a catastrophe card
     */
    #[PossibleAction]
    public function actPlayCatastrophe(int $card_id, int $activePlayerId, array $args)
    {
        $card = $this->game->cards->getCard($card_id);
        $this->validateCardInHand($card, $activePlayerId, $args);

        if (!str_starts_with($card['type'], 'catastrophe_')) {
            throw new UserException('This is not a catastrophe card');
        }

        // Execute catastrophe effect
        $removedCards = $this->game->executeCatastrophe($card['type'], $activePlayerId);

        // For catastrophes, check every player for orphaned enhancers
        foreach ($this->game->getPlayerIds() as $pid) {
            $orphaned = $this->cleanupOrphanedEnhancers($pid);
            foreach ($orphaned as $oCard) {
                $removedCards[] = ['card' => $oCard, 'player_id' => $pid];
            }
        }

        // Discard the catastrophe card
        $this->game->cards->moveCard($card_id, 'discard');

        $info = Game::getCardTypeInfo($card['type'], (int)$card['type_arg']);
        $this->bga->notify->all("catastrophePlayed", clienttranslate('${player_name} plays ${card_name}! ${nbr} card(s) removed from all habitats!'), [
            'player_id' => $activePlayerId,
            'player_name' => $this->game->getPlayerNameById($activePlayerId),
            'card_name' => $info['name'] ?? $card['type'],
            'catastrophe_card' => $card,
            'removed_cards' => $removedCards,
            'nbr' => count($removedCards),
        ]);

        return $this->afterCardPlayed($activePlayerId);
    }

    /**
     * Discard cards when unable to play 3
     */
    #[PossibleAction]
    public function actDiscard(string $card_ids, int $activePlayerId, array $args)
    {
        $cardIdsArray = array_map('intval', explode(',', $card_ids));
        $hand = $this->game->cards->getCardsInLocation('hand', $activePlayerId);
        $handCount = count($hand);
        $cardsPlayed = (int)$this->game->getGameStateValue(Game::GV_CARDS_PLAYED_THIS_CYCLE);
        $cardsRemaining = 3 - $cardsPlayed;

        if ($args['mustDiscard']) {
            // Mandatory discard down to 3 cards in hand
            $targetHand = 3;
            $neededDiscard = $handCount - $targetHand;

            if (count($cardIdsArray) !== $neededDiscard) {
                throw new UserException("You must discard exactly {$neededDiscard} card(s) to have 3 remaining.");
            }
            
            foreach ($cardIdsArray as $cid) {
                $this->discardCard($cid, $activePlayerId);
            }
            
            // Set cards played to 3 so we move on
            $this->game->setGameStateValue(Game::GV_CARDS_PLAYED_THIS_CYCLE, 3);
        } else {
            // Optional discard of 1 or more cards as actions
            $numDiscards = count($cardIdsArray);
            if ($numDiscards < 1 || $numDiscards > $cardsRemaining) {
                throw new UserException("You can discard between 1 and {$cardsRemaining} card(s) as your action(s).");
            }
            
            foreach ($cardIdsArray as $cid) {
                $this->discardCard($cid, $activePlayerId);
            }
            
            // Increment cards played by number of discards
            $played = $cardsPlayed + $numDiscards;
            $this->game->setGameStateValue(Game::GV_CARDS_PLAYED_THIS_CYCLE, $played);
        }

        $this->bga->notify->all("cardsDiscarded", clienttranslate('${player_name} discards ${nbr} card(s)'), [
            'player_id' => $activePlayerId,
            'player_name' => $this->game->getPlayerNameById($activePlayerId),
            'card_ids' => $cardIdsArray,
            'nbr' => count($cardIdsArray),
        ]);

        return NextPlayer::class;
    }

    private function discardCard(int $cardId, int $playerId): void
    {
        $card = $this->game->cards->getCard($cardId);
        if (!$card || $card['location'] !== 'hand' || (int)$card['location_arg'] !== $playerId) {
            throw new UserException('Invalid card to discard');
        }
        $this->game->cards->moveCard($cardId, 'discard');
    }

    /**
     * Check and remove enhancers that no longer have matching animals in a habitat
     */
    private function cleanupOrphanedEnhancers(int $playerId): array
    {
        $habitat = $this->game->cards->getCardsInLocation('habitat', $playerId);
        $lifeTypes = [];
        $enhancers = [];
        foreach ($habitat as $card) {
            if (Game::isLifeType($card['type'])) $lifeTypes[$card['type']] = true;
            if (str_starts_with($card['type'], 'enhancer_')) $enhancers[] = $card;
        }

        $removed = [];
        foreach ($enhancers as $enhancer) {
            $target = Game::ENHANCER_TARGETS[$enhancer['type']] ?? null;
            if ($target && !isset($lifeTypes[$target])) {
                $this->game->cards->moveCard((int)$enhancer['id'], 'discard');
                $removed[] = $enhancer;
            }
        }
        return $removed;
    }

    /**
     * Common logic after playing a card
     */
    private function afterCardPlayed(int $activePlayerId): string
    {
        $played = (int)$this->game->getGameStateValue(Game::GV_CARDS_PLAYED_THIS_CYCLE) + 1;
        $this->game->setGameStateValue(Game::GV_CARDS_PLAYED_THIS_CYCLE, $played);

        return NextPlayer::class;
    }

    /**
     * Validate that a card is in the player's hand and is playable
     */
    private function validateCardInHand(array $card, int $playerId, array $args): void
    {
        if (!$card || $card['location'] !== 'hand' || (int)$card['location_arg'] !== $playerId) {
            throw new UserException('This card is not in your hand');
        }
        if (!in_array((int)$card['id'], $args['playableCardIds'] ?? [])) {
            throw new UserException('This card cannot be played right now');
        }
    }

    function zombie(int $playerId) {
        // Zombie: play a random playable card or pass
        $args = $this->getArgs();
        $playable = $args['playableCards'];

        if (empty($playable)) {
            // Must discard
            $hand = $this->game->cards->getCardsInLocation('hand', $playerId);
            $handCards = array_values($hand);
            $cardsPlayed = $args['cardsPlayed'];
            $toDiscard = count($handCards) - 3;
            if ($toDiscard > 0) {
                $discardIds = array_map(fn($c) => (int)$c['id'], array_slice($handCards, 0, $toDiscard));
                return $this->actDiscard($discardIds, $playerId, $args);
            }
            return NextPlayer::class;
        }

        // Play a random card
        $card = $playable[array_rand($playable)];
        $category = Game::getCardCategory($card['type']);

        switch ($category) {
            case 'life':
                return $this->actPlayLifeCard((int)$card['id'], $playerId, $args);
            case 'enhancer':
                return $this->actPlayEnhancer((int)$card['id'], $playerId, $args);
            case 'rain':
                return $this->actPlayRain((int)$card['id'], $playerId, $args);
            case 'catastrophe':
                return $this->actPlayCatastrophe((int)$card['id'], $playerId, $args);
            case 'aggressor':
                if ($card['type'] === 'predator') {
                    // Find a random target with life cards
                    $others = $args['otherPlayers'];
                    foreach ($others as $op) {
                        if (!empty($op['habitatCards'])) {
                            $targetCard = $op['habitatCards'][array_rand($op['habitatCards'])];
                            return $this->actPlayPredator((int)$card['id'], (int)$op['id'], (int)$targetCard['id'], $playerId, $args);
                        }
                    }
                    // No valid target, play a life card instead
                    foreach ($playable as $pc) {
                        if (Game::isLifeType($pc['type'])) {
                            return $this->actPlayLifeCard((int)$pc['id'], $playerId, $args);
                        }
                    }
                } elseif ($card['type'] === 'hunter') {
                    $others = $args['otherPlayers'];
                    foreach ($others as $op) {
                        if (!empty($op['habitatCards'])) {
                            $lifeType = $op['habitatCards'][0]['type'];
                            return $this->actPlayHunter((int)$card['id'], (int)$op['id'], $lifeType, $playerId, $args);
                        }
                    }
                }
                // Fallback: play first life card
                foreach ($playable as $pc) {
                    if (Game::isLifeType($pc['type'])) {
                        return $this->actPlayLifeCard((int)$pc['id'], $playerId, $args);
                    }
                }
                return NextPlayer::class;
            default:
                return NextPlayer::class;
        }
    }
}
