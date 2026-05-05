# WildLife: Expansion Implementation Plan

This document outlines the strategy for adding expansions (e.g., "Deep Ocean", "High Skies") to the WildLife project, following the architectural patterns found in *Viticulture*.

## 1. Configuration (The "Entry Point")

Expansions are toggled by the user in the game lobby. This is handled via `gameoptions.json`.

### Step 1: Define Options
Add a new option ID (e.g., 100+) for each expansion.
```json
// gameoptions.json
{
  "100": {
    "name": "Expansion: Deep Ocean",
    "values": {
      "0": { "name": "Disabled" },
      "1": { "name": "Enabled", "tmdisplay": "Deep Ocean Expansion" }
    },
    "default": 0
  }
}
```

## 2. Server-Side Integration (PHP)

### Step 2: Accessing Options
In `Game.php`, read the option value and store it in a convenient way (like a static Helper or a Global variable).
```php
// Game.php -> setupNewGame
$isDeepOceanActive = $this->getGameStateValue('deep_ocean_option') == 1;
```

### Step 3: Conditional Material (`CardManager.php`)
Update the `CardManager` to include an `expansion` flag in card definitions. Use this to filter which cards are added to the deck during setup.

```php
// CardManager.php
public static function init(bool $includeExpansions = false): void {
    // ... base cards ...
    if ($includeExpansions) {
        self::initDeepOceanCards();
    }
}
```

### Step 4: Logic Adjustments (`HabitatManager.php`)
If an expansion adds new scoring rules (e.g., "Deep Ocean" cards provide bonuses based on depth), update the scoring logic to check for the presence of these card types.

```php
// HabitatManager.php
public static function calculateScore(int $playerId): int {
    $score = self::calculateBaseScore($playerId);
    if (Game::get()->isExpansionActive('deep_ocean')) {
        $score += self::calculateOceanBonus($playerId);
    }
    return $score;
}
```

## 3. Client-Side Integration (JS)

### Step 5: Conditional UI
Pass expansion flags to the frontend via `getAllDatas`. Update `Game.js` to render new UI elements (e.g., extra columns, new background art) only when the expansion is active.

```javascript
// Game.js -> setup
if (gamedatas.expansions.deep_ocean) {
    this.setupOceanColumns();
    document.body.classList.add('wld_ocean-theme');
}
```

### Step 6: Dynamic Tooltips and Labels
Ensure that new categories or life types introduced by expansions are added to the global maps (`LIFE_TYPE_LABELS`, `CATEGORY_COLORS`) in the JS layer.

## 4. State Machine Changes (`States/`)

If an expansion adds a new phase (e.g., "Exploration Phase" after the draw), add the state to `states.inc.php` and use a conditional transition in the previous state.

```php
// states.inc.php
"transitions" => [
    "next" => $this->isExpansionActive('exploration') ? 40 : 10,
]
```

## Summary of Action Items for a New Expansion
1. Update `gameoptions.json`.
2. Add new card definitions to `CardManager.php`.
3. Add new images to `img/cards/`.
4. Update `HabitatManager.php` scoring logic.
5. Update `Game.js` rendering and CSS.
6. (Optional) Add new states if the expansion changes the flow.

---

## 5. Tentative Future Improvements
- [ ] **Zombie Mode Upgrade (Level 2 - Greedy)**: Improve the `PlayerTurn` zombie logic to prioritize high-value plays or protection of high-scoring cards instead of purely random actions.
