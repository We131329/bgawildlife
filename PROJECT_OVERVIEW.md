# WildLife: The Card Game - Project Overview

This project is a BGA implementation of "WildLife". Players compete over a series of "Cycles" to build the highest-scoring habitat.

## Core Mechanics
- **Habitat Building**: Players play Life cards (Small, Big, Flying, Aquatic) to their personal habitat.
- **Enhancement**: Enhancer cards (Spring, Winter, Nesting, Spawning) provide multipliers for specific life types.
- **Interaction**:
    - **Predators**: Remove a single card from an opponent.
    - **Hunters**: Target an entire category of life. Triggers a reaction if the target has a **Protector**.
    - **Catastrophes**: Global events (Fire, Water) that affect all players simultaneously.
- **Scoring**: Occurs at the end of each cycle. Most points are cumulative, but **Rain** provides temporary points that disappear after scoring.

## Technical Architecture
- **Framework**: Modern BGA PHP framework using namespaces and State-pattern classes.
- **State Machine**:
    - `NewCycle`: Handles turn rotation and cycle setup.
    - `DrawPhase`: Players refill their hands to 6 cards.
    - `PlayerTurn`: The active player plays or discards 3 cards.
    - `ReactProtector`: An asynchronous-style interruption allowing a target to defend against a Hunter.
    - `EndCycle`: Calculates habitat scores and manages temporary card removal.
    - `EndScore`: Final tiebreaker logic using encoded cycle history.

## Suggested Improvements

### 1. Centralize Data Mappings
The mapping between Life Type integers (1-4) and strings (`small_life`, etc.) is currently hardcoded inside both `ReactProtector.php` and `PlayerTurn.php`. 
- **Action**: Move `$lifeTypeMap` to a constant or a static method in `Game.php` to ensure consistency and easier maintenance.

### 2. Standardize State Transitions
Most states return class references (e.g., `return PlayerTurn::class;`), but `EndScore.php` returns a numeric constant `ST_END_GAME`.
- **Action**: Update the state machine to use class-based references consistently if the framework version supports it, or ensure `ST_END_GAME` is correctly mapped in `states.inc.php`.

### 3. Tiebreaker Integer Safety
In `EndScore.php`, the tiebreaker `player_score_aux` is calculated by multiplying cycle scores by 1000^n. 
- **Risk**: While PHP handles 64-bit integers, very long games with high scores could theoretically approach `PHP_INT_MAX`. 
- **Action**: Add a check or use a slightly more compact encoding if the number of cycles increases.

### 4. Code Reuse in Aggression Logic
The logic for removing life cards and their associated enhancers is duplicated in `executeCatastrophe` and `executeHunter`.
- **Action**: Create a helper method in `Game.php` called `discardLifeAndEnhancers($playerId, $lifeTypes)` to handle the removal and notification logic in one place.

### 5. Translation Consistency
Some strings like "Lluvia" (Rain) are hardcoded in Spanish in the `Game.php` definitions, while others use `clienttranslate`.
- **Action**: Ensure all user-facing names in `self::$CARD_TYPES` are wrapped in `totranslate()` or `clienttranslate()` to support BGA's multi-language features.

### 6. Validation Logic
In `PlayerTurn.php`, `validateCardInHand` is a great start. 
- **Action**: Consider moving specific "Playability" rules (like needing a life card before an enhancer) into a dedicated `RulesEngine` trait or class to keep the State classes slim.

### 7. Global Values
`GV_ACTIVE_TURN_PLAYER` is set but not heavily used compared to the framework's `getActivePlayerId`. 
- **Action**: Verify if this is redundant or if it's intended to track the "Main" active player during reactive states.