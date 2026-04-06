# WildLife: The Card Game - Project Overview

This project is a BGA implementation of "WildLife". Players compete over a series of "Cycles" to build the highest-scoring habitat.

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

## Wrap Up & Polish Roadmap

### 1. Game Progression
- **Status**: Basic `getGameProgression()` exists in `Game.php`.
- **Goal**: Refine it to calculate progress based on the current cycle vs. total cycles (e.g., `($currentCycle - 1) / $totalCycles * 100`).

### 2. Comprehensive Zombie Mode
- **Status**: Implemented for `MulliganPhase` and `PlayerTurn`.
- **Goal**: Audit and add `zombie()` methods to all other state classes (`DrawPhase`, `ReactProtector`, etc.) to ensure the game never hangs if a player disconnects.

### 3. Statistics (stats.json)
- **Status**: `stats.json` is well-defined with categories like `life_cards_played`, `hunters_played`, and `highest_cycle_score`.
- **Goal**: Ensure all PHP actions (in `PlayerTurn`, `ReactProtector`, etc.) actually call `$this->game->playerStats->inc(...)` to update these values during play.

### 4. Game Logs & Notifications
- **Status**: Major actions have logs.
- **Goal**: Review all notifications to ensure the text (e.g., `${player_name} plays ${card_name}`) provides a clear history of the game in the log panel.

### 5. Tiebreaking
- **Status**: **Completed**. `EndScore.php` implements a base-128 tiebreaker, and `gameinfos.inc.php` correctly describes it as "Highest score in a single cycle".

### 6. Translation Audit
- **Status**: Most strings are wrapped.
- **Goal**: Final pass to ensure no hardcoded English/Spanish strings remain in JS or PHP.

### 7. UI Tooltips
- **Status**: Cards are rendered but lack interactive tooltips.
- **Goal**: Use `this.bga.tooltips.addTooltipHtml()` in `Game.js` to show card descriptions or effects when hovering over images.
