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
    - `MulliganPhase`: Simultaneous start where players can redraw their initial hand.
    - `NewCycle`: Handles turn rotation and cycle setup.
    - `DrawPhase`: Players refill their hands to 6 cards.
    - `PlayerTurn`: The active player plays or discards 3 cards.
    - `ReactProtector`: An asynchronous-style interruption allowing a target to defend against a Hunter.
    - `EndCycle`: Calculates habitat scores and manages temporary card removal.
    - `EndScore`: Final tiebreaker logic using encoded cycle history.

## Completed Improvements

### 1. Centralize Data Mappings (Completed)
Mappings between Life Type integers and strings are centralized in `Game.php` using `LIFE_TYPE_TO_ID` and `ID_TO_LIFE_TYPE` constants.

### 2. Standardize State Transitions (Completed)
State transitions use class-based references or constants consistently.

### 3. Tiebreaker Integer Safety (Verified)
The tiebreaker uses a base-128 encoding to fit safely within a 32-bit signed integer.

### 4. Code Reuse in Aggression Logic (Completed)
Centralized logic for card removal in `discardLifeAndEnhancers()`.

### 5. Translation Consistency (Completed)
All user-facing strings are wrapped in `clienttranslate()`.

### 6. Validation Logic (Completed)
Playability rules are centralized in the `RulesEngine` class.

### 7. Global Values (Verified)
`GV_ACTIVE_TURN_PLAYER` tracks the primary active player during multi-action turns.

### 8. Mulligan Feature (New)
At the start of the game:
- **First Player**: Can choose 1-6 cards to discard and replace.
- **Other Players**: Can choose to replace their entire 6-card hand or keep it.
- **Simultaneous**: All players decide at once to keep the game fast.
