# WildLife: The Card Game - Project Documentation

## Project Structure
```text
C:\Users\PC PRIDE WHITE WOLF\OneDrive\Documentos2\BGA\wildlife\
├── dbmodel.sql                 # Database schema definitions
├── gameinfos.inc.php           # Game metadata and configuration
├── gameoptions.json            # Table options configuration
├── gamepreferences.json        # User preferences configuration
├── stats.json                  # Game statistics definitions
├── wildlife.css                # Main stylesheet
├── img/                        # Graphic assets
│   └── cards/                  # Card artwork (subdivided by type)
├── modules/
│   ├── php/
│   │   ├── Game.php            # Main server-side logic and card definitions
│   │   ├── RulesEngine.php     # Static validation and game rule logic
│   │   └── States/             # State-pattern classes for game flow
│   │       ├── DrawPhase.php
│   │       ├── EndCycle.php
│   │       ├── EndScore.php
│   │       ├── MulliganPhase.php
│   │       ├── NewCycle.php
│   │       ├── NextPlayer.php
│   │       ├── PlayerTurn.php
│   │       └── ReactProtector.php
│   └── js/
│       └── Game.js             # Client-side UI and notification handlers
└── _ide_helper.php             # Local IDE autocompletion helper
```

## Purpose
**WildLife** is a strategic card game implemented for the Board Game Arena (BGA) platform. Players compete over several "Cycles" to build the most valuable habitat by playing and protecting various animal species.

The implementation uses a modern, class-based PHP architecture for its state machine, separating game logic into discrete state handlers (under `modules/php/States/`). Key features include:
- **Habitat Building**: Players play Small, Big, Flying, and Aquatic life cards.
- **Ecological Balance**: Enhancer cards multiply scores, while Aggressors (Predators/Hunters) and Catastrophes (Fire/Pollution) threaten opponent habitats.
- **Reactive Defense**: Players can respond to Hunters using Protector cards.
- **Multi-Cycle Scoring**: Scores are calculated at the end of each cycle, with complex tiebreaking logic based on historical cycle performance.

## Operation
As a BGA project, the primary "commands" are the action handlers within the PHP states and the corresponding AJAX calls in the JS layer.

- **Setup**: Initialized via `setupNewGame` in `Game.php`.
- **Game Flow**: Controlled by the State Machine (Transitioning between `MulliganPhase`, `DrawPhase`, `PlayerTurn`, etc.).
- **Client Actions**: Triggered via `bgaPerformAction` in `Game.js` (e.g., `actPlayLifeCard`, `actMulligan`).
- **Development**:
    - **Linting**: Standard PHP and JS linting.
    - **Studio Upload**: Files must be synced to the BGA Studio SFTP for testing.
    - **Database**: Schema updates require modifying `dbmodel.sql`.

## Skill: Syntax Guard & Validator
Cada vez que sugieras un cambio de código o edites un archivo, debes seguir este protocolo:

1. **Análisis Estático:** Revisa que no falten llaves `{}`, paréntesis `()`, o puntos y coma `;` según el lenguaje.
2. **Validación de Importaciones:** Asegúrate de que todas las librerías mencionadas en el código estén importadas o existan en el proyecto.
3. **Modo Diff:** Si el cambio es pequeño, no reescribas todo el archivo. Usa comentarios de tipo `// ... código anterior` para ahorrar tokens y solo muestra el bloque corregido.
4. **Verificación de Errores:** Si detectas que cometí un error de sintaxis en mi prompt, corrígelo inmediatamente antes de proceder con la lógica.

## Change Tracking Instruction
Every time I ask you for a summary of changes, compare the current state with this structure and list the modified files.
