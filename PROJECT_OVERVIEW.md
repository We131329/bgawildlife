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

---

## 🚀 Pre-Alpha Checklist Roadmap
Working through the official BGA pre-release requirements in order.

### Phase 1: Game Logic & Server Side (Core Integrity)
- [x] **Statistics (`stats.json`)**: Fixed argument order for `playerStats->inc` and `set`.
- [x] **Extra Time**: Ensure `giveExtraTime()` is called when giving turns (Updated in `NextPlayer.php` and `DrawPhase.php`).
- [x] **Game Progression**: Refine `getGameProgression()` in `Game.php` (Implemented logic based on cycles and player turns).
- [x] **Zombie Mode**: Comprehensive audit of `zombie()` methods in all states (Refined `PlayerTurn.php` and verified others).
- [x] **Notifications**: Final review of log messages for clarity and meaningfulness (Improved `hunterResolved` and others).
- [x] **Tiebreaking**: Implemented base-128 encoded aux score in `EndScore.php`.
- [x] **Database Integrity**: Verify no manual transactions or schema-changing queries (Verified queries are standard DML).

### Phase 2: User Interface & Client Side (UX & Polish)
- [x] **Ajax Safety**: Verify `bgaPerformAction` is only triggered by user actions (Verified in `Game.js`).
- [x] **UI Centering**: Ensure game zone elements are centered (Added centering and max-width in CSS).
- [x] **Tooltips**: Add non-self-explanatory graphic tooltips (Implemented using `addTooltipHtml`).
- [x] **Translation Audit**: Ensure all strings (PHP/JS) use `clienttranslate()` or `_()` (Wrapped all hardcoded UI strings).
- [x] **CSS Namespacing**: Ensure all CSS classes use a game-specific prefix (Renamed all `wl-` to `wld_`).
- [x] **High Res Support**: Check for blurriness at high zoom (Added `image-rendering` optimization).
- [x] **Grammar & Gender**: Review English messages for punctuation, present tense, and gender neutrality (Verified "their" usage).

### Phase 3: Assets, Metadata & Licensing (Packaging)
- [ ] **License Check**: Confirm BGA has the license for WildLife.
- [ ] **Metadata Manager**: Update `gameinfos.inc.php` and upload pretty images in Metadata Manager.
- [ ] **Game Box**: 3D version of the box with transparent background.
- [ ] **Image Compression**: 
    - [ ] Compress cards into "Sprites" (atlases).
    - [ ] Ensure individual files < 4MB.
    - [ ] Total assets size < 15MB (or indexed palette optimization).
- [ ] **Cleanup**: Remove all unused images from the `img` directory.
- [ ] **Sounds & Fonts**: Move assets to `sounds/` and `fonts/` folders respectively; include license `.txt` for fonts.

### Phase 4: Special Testing & Validation
- [ ] **Minification Test**: Test game with "Use minified JS/CSS" enabled in Studio.
- [ ] **Spectator Mode**: Test as a non-player observer (verify no private info is leaked).
- [ ] **Replay Mode**: Test in-game replay (log clicks) and end-of-game full replay.
- [ ] **Browser/Mobile**: Test on Chrome, Firefox, and mobile/responsive views.
- [ ] **Realtime Mode**: Verify no time-outs occur with `giveExtraTime()`.
- [ ] **Waiting Screen**: Verify the game starts correctly through the waiting screen.

### Phase 5: Final Cleanup & Alpha Request
- [ ] **Code Cleanup**:
    - [ ] Remove all `console.log` (except `console.error`).
    - [ ] Remove all PHP debug logging.
    - [ ] Ensure copyright headers have your name.
- [ ] **Static Analysis**: Run "Dry run build" and "Check project" in the control panel.
- [ ] **Alpha Request**: Build a release version and click "Request ALPHA status".
- [ ] **Email Follow-up**: Prepare info for the BGA admin email (Renaming, License, Usernames).
