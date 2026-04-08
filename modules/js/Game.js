/**
 * Wild Life: The Card Game - BGA Implementation
 * JavaScript Game Logic & UI
 */

// Life type labels for display
const LIFE_TYPE_LABELS = {
    'small_life': _('Small Life'),
    'big_life': _('Big Life'),
    'flying_life': _('Flying Life'),
    'aquatic_life': _('Aquatic Life'),
};

const LIFE_TYPE_ICONS = {
    'small_life': '🐿️',
    'big_life': '🦌',
    'flying_life': '🐦',
    'aquatic_life': '🐟',
};

const CATEGORY_COLORS = {
    'life': '#6aa84f',
    'enhancer': '#b8860b',
    'rain': '#5bb5b5',
    'protector': '#e94190',
    'aggressor': '#cc0000',
    'catastrophe': '#1a1a1a',
};

/**
 * Helper: Get card category from type string
 */
function getCardCategory(cardType) {
    if (['small_life', 'big_life', 'flying_life', 'aquatic_life'].includes(cardType)) return 'life';
    if (cardType.startsWith('enhancer_')) return 'enhancer';
    if (cardType === 'rain') return 'rain';
    if (cardType === 'protector') return 'protector';
    if (cardType === 'predator' || cardType === 'hunter') return 'aggressor';
    if (cardType.startsWith('catastrophe_')) return 'catastrophe';
    return 'unknown';
}

/**
 * Helper: Get card info from gamedatas
 */
function getCardTypeInfo(cardTypes, card) {
    const key = `${card.type}_${card.type_arg}`;
    return cardTypes[key] || null;
}

// ============================================================
// State: MulliganPhase
// ============================================================
class MulliganPhase {
    constructor(game, bga) {
        this.game = game;
        this.bga = bga;
    }

    onEnteringState(args, isCurrentPlayerActive) {
        this.bga.statusBar.removeActionButtons();
        const isFirstPlayer = this.bga.players.getCurrentPlayerId() == args.firstPlayerId;
        const status = args.mulliganStatus[this.bga.players.getCurrentPlayerId()];

        if (isCurrentPlayerActive && status == 0) {
            this.game.selectedCards.clear();
            
            if (isFirstPlayer) {
                this.bga.statusBar.setTitle(_('${you}: Select 1-6 cards to mulligan, or accept your hand'));
                this.game.enableMulliganSelection(6); // Max 6
            } else {
                this.bga.statusBar.setTitle(_('${you}: Mulligan ALL your cards, or accept your hand'));
                // For others, clicking Mulligan just selects all
            }

            this.bga.statusBar.addActionButton(
                _('Mulligan'),
                () => this.onMulliganClick(isFirstPlayer),
                { color: 'red', id: 'btn_mulligan' }
            );

            this.bga.statusBar.addActionButton(
                _('Accept Hand'),
                () => this.bga.actions.performAction("actAcceptHand"),
                { color: 'blue', id: 'btn_accept_hand' }
            );
        } else {
            this.bga.statusBar.setTitle(_('Waiting for other players to finish their mulligans'));
            this.game.disableCardSelection();
        }
    }

    onMulliganClick(isFirstPlayer) {
        if (!isFirstPlayer) {
            // Select all cards automatically for non-first player
            const allIds = Array.from(document.querySelectorAll('.wld_hand-card')).map(el => parseInt(el.dataset.cardId));
            this.bga.actions.performAction("actMulligan", { cardIds: allIds });
            return;
        }

        // First player needs to have selected cards
        if (this.game.selectedCards.size === 0) {
            this.bga.dialogs.showMessage(_('Select at least one card to mulligan'), 'error');
            return;
        }
        
        const cardIds = Array.from(this.game.selectedCards);
        this.bga.actions.performAction("actMulligan", { cardIds: cardIds });
    }

    onLeavingState(args, isCurrentPlayerActive) {
        this.game.disableCardSelection();
    }

    onPlayerActivationChange(args, isCurrentPlayerActive) {
        this.onEnteringState(args, isCurrentPlayerActive);
    }
}

// ============================================================
// State: PlayerTurn
// ============================================================
class PlayerTurn {
    constructor(game, bga) {
        this.game = game;
        this.bga = bga;
    }

    onEnteringState(args, isCurrentPlayerActive) {
        if (isCurrentPlayerActive) {
            if (args.mustDiscard) {
                this.game.enableDiscardMode(args);
            } else {
                this.game.enableCardSelection(args);
                
                // Add optional discard toggle
                if (args.canDiscard) {
                    this.bga.statusBar.addActionButton(
                        _('Discard cards'),
                        () => this.game.toggleDiscardMode(),
                        { color: 'secondary' }
                    );
                }
            }
        } else {
            this.game.currentArgs = args;
            this.game.updateTurnTitle();
        }
    }

    onLeavingState(args, isCurrentPlayerActive) {
        this.game.disableCardSelection();
    }

    onPlayerActivationChange(args, isCurrentPlayerActive) {}
}

// ============================================================
// State: ReactProtector
// ============================================================
class ReactProtectorState {
    constructor(game, bga) {
        this.game = game;
        this.bga = bga;
    }

    onEnteringState(args, isCurrentPlayerActive) {
        const lifeLabel = LIFE_TYPE_LABELS[args.targetedLifeType] || args.targetedLifeType;
        const hasProtectors = args.protectors && args.protectors.length > 0;

        if (isCurrentPlayerActive) {
            const title = hasProtectors ?
                _('${you}: A Hunter targets your ${life_type}! Play a Protector?').replace('${life_type}', lifeLabel) :
                _('${you}: A Hunter targets your ${life_type}! (No Protector in hand)').replace('${life_type}', lifeLabel);
            this.bga.statusBar.setTitle(title);

            // Add protector buttons
            args.protectors.forEach(card => {
                this.bga.statusBar.addActionButton(
                    _('Use Protector'),
                    () => this.bga.actions.performAction("actUseProtector", { card_id: card.id })
                );
            });

            const declineLabel = hasProtectors ? _('Decline (lose your animals)') : _('Acknowledge (lose your animals)');
            this.bga.statusBar.addActionButton(
                declineLabel,
                () => this.bga.actions.performAction("actDeclineProtector"),
                { color: 'secondary' }
            );
        } else {
            this.bga.statusBar.setTitle(_('${actplayer} is deciding whether to use a Protector'));
        }
    }

    onLeavingState(args, isCurrentPlayerActive) {}
    onPlayerActivationChange(args, isCurrentPlayerActive) {}
}

// ============================================================
// Main Game Class
// ============================================================
export class Game {
    constructor(bga) {
        console.log('Wild Life constructor');
        this.bga = bga;

        // Register states
        this.mulliganPhase = new MulliganPhase(this, bga);
        this.bga.states.register('MulliganPhase', this.mulliganPhase);

        this.playerTurn = new PlayerTurn(this, bga);
        this.bga.states.register('PlayerTurn', this.playerTurn);

        this.reactProtector = new ReactProtectorState(this, bga);
        this.bga.states.register('ReactProtector', this.reactProtector);

        this.selectedCards = new Set();
        this.discardMode = false;
        this.currentArgs = null;
    }

    setup(gamedatas) {
        console.log("Starting Wild Life setup");
        this.gamedatas = gamedatas;
        this.cardTypes = gamedatas.cardTypes;

        // Create main game layout
        this.bga.gameArea.getElement().insertAdjacentHTML('beforeend', `
            <div id="wld_game-info">
                <span id="wld_cycle-info">${_('Cycle')} <span id="wld_current-cycle">${gamedatas.currentCycle}</span> / <span id="wld_total-cycles">${gamedatas.totalCycles}</span></span>
                <span id="wld_deck-info">${_('Deck:')} <span id="wld_deck-count">${gamedatas.deckCount}</span></span>
                <span id="wld_discard-info">${_('Discard:')} <span id="wld_discard-count">${gamedatas.discardCount}</span></span>
            </div>
            <div id="wld_my-hand-wrapper">
                <h3>${_('My Hand')}</h3>
                <div id="wld_my-hand"></div>
            </div>
            <div id="wld_my-habitat-wrapper">
                <h3 id="wld_my-habitat-title">${_('My Habitat')}</h3>
                <div id="wld_my-habitat"></div>
            </div>
            <div id="wld_habitats" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;"></div>
        `);

        // Setup player panels and habitats
        const currentPlayerId = this.bga.players.getCurrentPlayerId();

        Object.values(gamedatas.players).forEach(player => {
            // Player panel with score
            this.bga.playerPanels.getElement(player.id).insertAdjacentHTML('beforeend', `
                <div class="wld_panel-info">
                    <span class="wld_panel-cards">🃏 <span id="wld_hand-count-${player.id}">${gamedatas.handCounts[player.id] || 0}</span></span>
                </div>
            `);

            // Opponent habitat zones
            if (parseInt(player.id) !== currentPlayerId) {
                const tokenImg = this.bga.images.getImgUrl('cards/special/firstToken.jpg');
                document.getElementById('wld_habitats').insertAdjacentHTML('beforeend', `
                    <div class="wld_opponent-habitat" id="wld_habitat-${player.id}">
                        <div class="wld_habitat-header" style="border-color: #${player.color}">
                            <strong>${player.name}</strong>
                            <span class="wld_habitat-score" id="wld_habitat-score-${player.id}">0 ${_('pts')}</span>
                            <div class="wld_first-player-token" id="wld_token_${player.id}" style="background-image: url('${tokenImg}')"></div>
                        </div>
                        <div class="wld_habitat-grid" id="wld_habitat-grid-${player.id}">
                            <div class="wld_habitat-column wld_habitat-extras" id="wld_hab-${player.id}-extras"><div class="wld_col-label">🌧️ ${_('Rain')}</div></div>
                            <div class="wld_habitat-column" data-type="small_life" id="wld_hab-${player.id}-small_life"><div class="wld_col-label">🐿️ ${_('Small')}</div></div>
                            <div class="wld_habitat-column" data-type="big_life" id="wld_hab-${player.id}-big_life"><div class="wld_col-label">🦌 ${_('Big')}</div></div>
                            <div class="wld_habitat-column" data-type="flying_life" id="wld_hab-${player.id}-flying_life"><div class="wld_col-label">🐦 ${_('Flying')}</div></div>
                            <div class="wld_habitat-column" data-type="aquatic_life" id="wld_hab-${player.id}-aquatic_life"><div class="wld_col-label">🐟 ${_('Aquatic')}</div></div>
                        </div>
                    </div>
                `);
            }
        });

        // My habitat
        const myHab = document.getElementById('wld_my-habitat');
        const myTokenImg = this.bga.images.getImgUrl('cards/special/firstToken.jpg');
        myHab.innerHTML = `
            <div class="wld_habitat-header" style="border-bottom:none; padding:0; margin-bottom:10px;">
                <div class="wld_first-player-token" id="wld_token_${currentPlayerId}" style="background-image: url('${myTokenImg}')"></div>
            </div>
            <div class="wld_habitat-grid wld_my-grid">
                <div class="wld_habitat-column wld_my-col wld_habitat-extras" id="wld_my-hab-extras"><div class="wld_col-label">🌧️ ${_('Rain')}</div></div>
                <div class="wld_habitat-column wld_my-col" data-type="small_life" id="wld_my-hab-small_life"><div class="wld_col-label">🐿️ ${_('Small Life')}</div></div>
                <div class="wld_habitat-column wld_my-col" data-type="big_life" id="wld_my-hab-big_life"><div class="wld_col-label">🦌 ${_('Big Life')}</div></div>
                <div class="wld_habitat-column wld_my-col" data-type="flying_life" id="wld_my-hab-flying_life"><div class="wld_col-label">🐦 ${_('Flying Life')}</div></div>
                <div class="wld_habitat-column wld_my-col" data-type="aquatic_life" id="wld_my-hab-aquatic_life"><div class="wld_col-label">🐟 ${_('Aquatic Life')}</div></div>
            </div>
        `;

        // Initial token state
        this.updateFirstPlayerToken(gamedatas.firstPlayer);

        // Render existing habitat cards
        Object.entries(gamedatas.habitats).forEach(([pid, cards]) => {
            cards.forEach(card => this.placeCardInHabitat(card, parseInt(pid)));
        });

        // Render hand
        this.renderHand(gamedatas.hand);

        this.setupNotifications();
        console.log("Ending Wild Life setup");
    }

    // ============================================================
    // Card Rendering
    // ============================================================

    createCardElement(card, size = 'normal') {
        const info = getCardTypeInfo(this.cardTypes, card);
        const category = getCardCategory(card.type);
        const borderColor = CATEGORY_COLORS[category] || '#999';
        const sizeClass = size === 'small' ? 'wld_card-small' : 'wld_card';

        const name = info ? info.name : card.type;
        let image = info ? info.image : '';

        // Enforce Predator art and allow Hunter variety from the threats folder
        if (card.type === 'predator') {
            image = 'cards/threats/predator.jpg';
        }

        const div = document.createElement('div');
        div.className = `${sizeClass} wld_card-${category}`;
        div.id = `wld_card-${card.id}`;
        div.dataset.cardId = card.id;
        div.dataset.cardType = card.type;
        div.dataset.cardTypeArg = card.type_arg;
        div.style.borderColor = borderColor;

        if (image) {
            const imgUrl = this.bga.images.getImgUrl(image);
            div.innerHTML = `<img class="wld_card-img" src="${imgUrl}" alt="${name}" onerror="this.style.display='none'; if(this.nextElementSibling) this.nextElementSibling.style.display='flex';" /><div class="wld_card-fallback" style="display:none">${name}</div>`;
        } else {
            div.innerHTML = `<div class="wld_card-fallback">${name}</div>`;
        }

        // Add point indicator for life cards
        if (category === 'life' && info) {
            const pts = info.points || '?';
            const icon = LIFE_TYPE_ICONS[card.type] || '';
            div.innerHTML += `<div class="wld_card-points">${icon} ${pts === 0 ? '📊' : '🐾'.repeat(pts)}</div>`;
        }

        // Add multiplier for enhancers
        if (category === 'enhancer' && info) {
            div.innerHTML += `<div class="wld_card-multiplier">${info.multiplier}x</div>`;
        }

        // Rain indicator
        if (card.type === 'rain') {
            div.innerHTML += `<div class="wld_card-points">🐾🐾🐾</div>`;
        }

        // Add tooltip
        this.addTooltipToCard(div, card, info, category);

        return div;
    }

    addTooltipToCard(element, card, info, category) {
        let title = info ? info.name : card.type;
        let description = '';

        switch (category) {
            case 'life':
                const lifeType = LIFE_TYPE_LABELS[card.type] || card.type;
                if (card.type === 'aquatic_life') {
                    description = _('Aquatic animal. Scoring: 1:1pt, 2:3pts, 3:6pts, 4:10pts, 5+:15pts.');
                } else {
                    description = _('${life_type} animal. Worth ${n} point(s).').replace('${life_type}', lifeType).replace('${n}', info.points || 0);
                }
                break;
            case 'enhancer':
                const target = LIFE_TYPE_LABELS[info.target_life] || info.target_life;
                description = _('Multiplies the score of your ${target} animals by ${n}.').replace('${target}', target).replace('${n}', info.multiplier);
                break;
            case 'rain':
                description = _('Temporary card. Worth 3 points at the end of this cycle only.');
                break;
            case 'protector':
                description = _('Protects your habitat. Can be played when an opponent uses a Hunter against you.');
                break;
            case 'aggressor':
                if (card.type === 'predator') {
                    description = _('Aggressor. Discard one life card from an opponent\'s habitat.');
                } else {
                    description = _('Aggressor. Discard ALL cards of a specific life type from an opponent\'s habitat.');
                }
                break;
            case 'catastrophe':
                if (card.type === 'catastrophe_fire') {
                    description = _('Catastrophe. Removes all Small, Big, and Flying animals from ALL habitats.');
                } else if (card.type === 'catastrophe_water') {
                    description = _('Catastrophe. Removes all Aquatic animals from ALL habitats.');
                } else {
                    description = _('Catastrophe. Removes ALL animals from ALL habitats.');
                }
                break;
        }

        this.bga.gameui.addTooltipHtml(element.id, `
            <div class="wld_tooltip">
                <strong>${title}</strong><br/>
                <small>${category.toUpperCase()}</small><hr/>
                <div>${description}</div>
            </div>
        `);
    }

    placeCardInHabitat(card, playerId) {
        const currentPlayerId = this.bga.players.getCurrentPlayerId();
        const isMe = playerId === currentPlayerId;
        const prefix = isMe ? 'wld_my-hab' : `wld_hab-${playerId}`;
        const size = isMe ? 'normal' : 'small';

        const category = getCardCategory(card.type);
        let targetCol;

        if (['small_life', 'big_life', 'flying_life', 'aquatic_life'].includes(card.type)) {
            targetCol = document.getElementById(`${prefix}-${card.type}`);
        } else if (card.type.startsWith('enhancer_')) {
            // Place enhancer in the column of its target life type
            const targetLife = {
                'enhancer_spring': 'small_life',
                'enhancer_winter': 'big_life',
                'enhancer_nesting': 'flying_life',
                'enhancer_spawning': 'aquatic_life',
            }[card.type];
            targetCol = document.getElementById(`${prefix}-${targetLife || 'extras'}`);
        } else {
            targetCol = document.getElementById(`${prefix}-extras`);
        }

        if (targetCol) {
            const cardEl = this.createCardElement(card, size);
            targetCol.appendChild(cardEl);
        }
    }

    removeCardFromHabitat(cardId) {
        const el = document.getElementById(`wld_card-${cardId}`);
        if (el) el.remove();
    }

    renderHand(cards) {
        const handEl = document.getElementById('wld_my-hand');
        handEl.innerHTML = '';
        cards.forEach(card => {
            const cardEl = this.createCardElement(card, 'normal');
            cardEl.classList.add('wld_hand-card');
            handEl.appendChild(cardEl);
        });
    }

    addCardToHand(card) {
        const handEl = document.getElementById('wld_my-hand');
        const cardEl = this.createCardElement(card, 'normal');
        cardEl.classList.add('wld_hand-card');
        handEl.appendChild(cardEl);
    }

    removeCardFromHand(cardId) {
        const el = document.getElementById(`wld_card-${cardId}`);
        if (el) el.remove();
    }

    updateFirstPlayerToken(firstPlayerId) {
        // Hide all tokens
        document.querySelectorAll('.wld_first-player-token').forEach(el => {
            el.classList.remove('wld_visible');
        });

        // Show only the one for the first player
        const activeToken = document.getElementById(`wld_token_${firstPlayerId}`);
        if (activeToken) {
            activeToken.classList.add('wld_visible');
        }
    }

    // ============================================================
    // Card Selection / Interaction
    // ============================================================

    enableMulliganSelection(maxCards) {
        this.selectedCards.clear();
        document.querySelectorAll('.wld_hand-card').forEach(el => {
            el.classList.add('wld_selectable');
            el.classList.remove('wld_dimmed', 'wld_selected');
            el.onclick = () => this.onMulliganCardClick(parseInt(el.dataset.cardId), el, maxCards);
        });
    }

    onMulliganCardClick(cardId, el, maxCards) {
        if (this.selectedCards.has(cardId)) {
            this.selectedCards.delete(cardId);
            el.classList.remove('wld_selected');
        } else {
            if (this.selectedCards.size < maxCards) {
                this.selectedCards.add(cardId);
                el.classList.add('wld_selected');
            }
        }
    }

    enableCardSelection(args) {
        this.currentArgs = args;
        this.discardMode = false;
        const playableIds = args.playableCardIds || [];

        // Set the base title for the turn
        this.updateTurnTitle();

        document.querySelectorAll('.wld_hand-card').forEach(el => {
            const cardId = parseInt(el.dataset.cardId);
            if (playableIds.includes(cardId)) {
                el.classList.add('wld_selectable');
                el.onclick = () => this.onHandCardClick(cardId, el);
                el.classList.remove('wld_dimmed');
            } else {
                el.classList.add('wld_dimmed');
                el.classList.remove('wld_selectable');
                el.onclick = null;
            }
        });
    }

    updateTurnTitle() {
        if (!this.currentArgs) return;
        const remaining = this.currentArgs.cardsRemaining;
        const isCurrentPlayerActive = this.bga.players.isCurrentPlayerActive();
        
        if (this.currentArgs.mustDiscard) {
            this.bga.statusBar.setTitle(isCurrentPlayerActive ?
                _('${you} must discard cards (cannot play any more cards)') :
                _('${actplayer} must discard cards')
            );
        } else {
            this.bga.statusBar.setTitle(isCurrentPlayerActive ?
                _('${you} must play a card (${n} remaining)').replace('${n}', remaining) :
                _('${actplayer} must play a card (${n} remaining)').replace('${n}', remaining)
            );
        }
    }

    enableDiscardMode(args) {
        this.currentArgs = args;
        this.discardMode = true;
        this.selectedCards.clear();

        this.updateTurnTitle();

        const handCount = document.querySelectorAll('.wld_hand-card').length;
        this.discardTarget = handCount - 3;

        document.querySelectorAll('.wld_hand-card').forEach(el => {
            el.classList.add('wld_selectable');
            el.classList.remove('wld_dimmed');
            el.onclick = () => this.onDiscardCardClick(parseInt(el.dataset.cardId), el);
        });

        this.updateDiscardConfirmButton();
    }

    disableCardSelection() {
        document.querySelectorAll('.wld_hand-card').forEach(el => {
            el.classList.remove('wld_selectable', 'wld_dimmed', 'wld_selected', 'wld_target-selectable');
            el.onclick = null;
        });
        this.selectedCards.clear();
        this.discardMode = false;
    }

    toggleDiscardMode() {
        if (this.discardMode) {
            this.bga.statusBar.removeActionButtons();
            this.disableCardSelection();
            this.enableCardSelection(this.currentArgs);
            
            // Re-add the discard button since we just re-enabled card selection
            if (this.currentArgs.canDiscard) {
                this.bga.statusBar.addActionButton(
                    _('Discard cards'),
                    () => this.toggleDiscardMode(),
                    { color: 'secondary' }
                );
            }
        } else {
            this.discardMode = true;
            this.selectedCards.clear();
            this.bga.statusBar.removeActionButtons();
            this.bga.statusBar.setTitle(_('Select up to ${n} card(s) to discard').replace('${n}', this.currentArgs.cardsRemaining));
            document.querySelectorAll('.wld_hand-card').forEach(el => {
                el.classList.remove('wld_dimmed');
                el.classList.add('wld_selectable');
                el.onclick = () => this.onDiscardCardClick(parseInt(el.dataset.cardId), el);
            });
            this.bga.statusBar.addActionButton(_('Cancel Discard'), () => this.toggleDiscardMode(), { color: 'secondary' });
        }
    }

    onHandCardClick(cardId, el) {
        if (this.discardMode) {
            this.onDiscardCardClick(cardId, el);
            return;
        }

        const card = this.findCardInHand(cardId);
        if (!card) return;

        const category = getCardCategory(card.type);

        switch (category) {
            case 'life':
                this.bga.actions.performAction("actPlayLifeCard", { card_id: cardId });
                break;
            case 'enhancer':
                this.bga.actions.performAction("actPlayEnhancer", { card_id: cardId });
                break;
            case 'rain':
                this.bga.actions.performAction("actPlayRain", { card_id: cardId });
                break;
            case 'catastrophe':
                this.bga.actions.performAction("actPlayCatastrophe", { card_id: cardId });
                break;
            case 'aggressor':
                this.showTargetSelection(card);
                break;
        }
    }

    onDiscardCardClick(cardId, el) {
        const maxDiscards = this.currentArgs.mustDiscard ? this.discardTarget : this.currentArgs.cardsRemaining;

        if (this.selectedCards.has(cardId)) {
            this.selectedCards.delete(cardId);
            el.classList.remove('wld_selected');
        } else {
            if (this.selectedCards.size < maxDiscards) {
                this.selectedCards.add(cardId);
                el.classList.add('wld_selected');
            }
        }

        // Update confirm button
        this.updateDiscardConfirmButton();
    }

    updateDiscardConfirmButton() {
        const existingBtn = document.getElementById('btn_confirm_discard');
        const shouldShow = this.currentArgs.mustDiscard || this.selectedCards.size > 0;
        
        if (shouldShow) {
            if (!existingBtn) {
                this.bga.statusBar.addActionButton(
                    _('Confirm Discard'),
                    () => this.confirmDiscard(),
                    { color: 'red', id: 'btn_confirm_discard' }
                );
            }
        } else if (existingBtn) {
            existingBtn.remove();
        }
    }

    confirmDiscard() {
        if (this.currentArgs.mustDiscard && this.selectedCards.size !== this.discardTarget) {
            this.bga.dialogs.showMessage(_('Select exactly ${n} card(s) to discard').replace('${n}', this.discardTarget), 'error');
            return;
        }
        const cardIds = Array.from(this.selectedCards).join(',');
        this.bga.actions.performAction("actDiscard", { card_ids: cardIds });
    }

    showTargetSelection(card) {
        if (!this.bga.actions.checkAction('actPlay' + (card.type === 'predator' ? 'Predator' : 'Hunter'), true)) {
            return;
        }

        // Disable hand interaction while selecting a target player or card
        this.disableCardSelection();

        const otherPlayers = this.currentArgs.otherPlayers || {};

        // Clear current status bar and add target buttons
        this.bga.statusBar.setTitle(_('Choose a target player'));

        Object.values(otherPlayers).forEach(op => {
            if (card.type === 'predator') {
                // For predator, need to target a specific card
                if (op.hasLifeCards) {
                    this.bga.statusBar.addActionButton(
                        op.name,
                        () => this.showPredatorCardChoice(card, op)
                    );
                }
            } else if (card.type === 'hunter') {
                // For hunter, need to target a life type
                if (op.hasLifeCards) {
                    this.bga.statusBar.addActionButton(
                        op.name,
                        () => this.showHunterTypeChoice(card, op)
                    );
                }
            }
        });

        this.bga.statusBar.addActionButton(_('Cancel'), () => {
            // Re-enter current state
            const args = this.currentArgs;
            this.bga.statusBar.removeActionButtons();
            this.disableCardSelection();
            this.enableCardSelection(args);
        }, { color: 'secondary' });
    }

    showPredatorCardChoice(predatorCard, targetPlayer) {
        this.bga.statusBar.removeActionButtons();
        this.bga.statusBar.setTitle(_('Choose a card to remove from ${name}\'s habitat').replace('${name}', targetPlayer.name));

        // Highlight clickable cards in opponent's habitat
        targetPlayer.habitatCards.forEach(hCard => {
            const el = document.getElementById(`wld_card-${hCard.id}`);
            if (el) {
                el.classList.add('wld_target-selectable');
                el.onclick = () => {
                    // Remove highlights
                    document.querySelectorAll('.wld_target-selectable').forEach(e => {
                        e.classList.remove('wld_target-selectable');
                        e.onclick = null;
                    });
                    this.bga.actions.performAction("actPlayPredator", {
                        card_id: predatorCard.id,
                        target_player_id: targetPlayer.id,
                        target_card_id: hCard.id,
                    });
                };
            }
        });

        this.bga.statusBar.addActionButton(_('Cancel'), () => {
            document.querySelectorAll('.wld_target-selectable').forEach(e => {
                e.classList.remove('wld_target-selectable');
                e.onclick = null;
            });
            const args = this.currentArgs;
            this.bga.statusBar.removeActionButtons();
            this.disableCardSelection();
            this.enableCardSelection(args);
        }, { color: 'secondary' });
    }

    showHunterTypeChoice(hunterCard, targetPlayer) {
        this.bga.statusBar.removeActionButtons();
        this.bga.statusBar.setTitle(_('Choose which life type to eliminate from ${name}').replace('${name}', targetPlayer.name));

        // Get unique life types in target's habitat
        const lifeTypes = new Set();
        targetPlayer.habitatCards.forEach(c => lifeTypes.add(c.type));

        lifeTypes.forEach(lt => {
            const icon = LIFE_TYPE_ICONS[lt] || '';
            const label = LIFE_TYPE_LABELS[lt] || lt;
            this.bga.statusBar.addActionButton(
                `${icon} ${label}`,
                () => {
                    this.bga.actions.performAction("actPlayHunter", {
                        card_id: hunterCard.id,
                        target_player_id: targetPlayer.id,
                        life_type: lt,
                    });
                }
            );
        });

        this.bga.statusBar.addActionButton(_('Cancel'), () => {
            const args = this.currentArgs;
            this.bga.statusBar.removeActionButtons();
            this.disableCardSelection();
            this.enableCardSelection(args);
        }, { color: 'secondary' });
    }

    findCardInHand(cardId) {
        return (this.gamedatas.hand || []).find(c => parseInt(c.id) === cardId) ||
               { id: cardId, type: document.getElementById(`wld_card-${cardId}`)?.dataset.cardType, type_arg: document.getElementById(`wld_card-${cardId}`)?.dataset.cardTypeArg };
    }

    // ============================================================
    // Notifications
    // ============================================================

    setupNotifications() {
        console.log('Wild Life notifications setup');
        this.bga.notifications.setupPromiseNotifications({
            mulliganResult: 500
        });
    }

    async notif_mulliganResult(args) {
        if (args.hand) {
            this.gamedatas.hand = args.hand;
            this.renderHand(args.hand);
        }
    }

    async notif_newCycle(args) {
        document.getElementById('wld_current-cycle').textContent = args.cycle_num;
        this.updateFirstPlayerToken(args.first_player);
    }

    async notif_cardsDrawn(args) {
        // Private notification - add cards to my hand
        if (args.cards) {
            args.cards.forEach(card => {
                this.addCardToHand(card);
                // Update local gamedatas
                if (!this.gamedatas.hand) this.gamedatas.hand = [];
                this.gamedatas.hand.push(card);
            });
        }
    }

    async notif_playerDrew(args) {
        // Update hand count display
        const countEl = document.getElementById(`wld_hand-count-${args.player_id}`);
        if (countEl) countEl.textContent = args.handCount;

        // Update deck count
        const deckEl = document.getElementById('wld_deck-count');
        if (deckEl) deckEl.textContent = args.deckCount;
    }

    async notif_cardPlayedToHabitat(args) {
        const card = args.card;
        const playerId = args.player_id;

        // Remove from hand if it's current player
        if (playerId == this.bga.players.getCurrentPlayerId()) {
            this.removeCardFromHand(card.id);

            // Remove from local hand data
            if (this.gamedatas.hand) {
                this.gamedatas.hand = this.gamedatas.hand.filter(c => c.id !== card.id);
            }
        }

        // Place in habitat
        this.placeCardInHabitat(card, playerId);

        // Update hand count
        const countEl = document.getElementById(`wld_hand-count-${playerId}`);
        if (countEl) countEl.textContent = parseInt(countEl.textContent) - 1;
    }

    async notif_enhancerLost(args) {
        // Remove all orphaned enhancers sent by the server
        if (args.removed_cards) {
            args.removed_cards.forEach(card => {
                this.removeCardFromHabitat(card.id);
            });
        }
    }

    async notif_predatorPlayed(args) {
        // Remove the targeted card from habitat
        this.removeCardFromHabitat(args.removed_card.id);

        // Remove predator card from hand if it's current player
        if (args.player_id == this.bga.players.getCurrentPlayerId()) {
            this.removeCardFromHand(args.predator_card.id);
            if (this.gamedatas.hand) {
                this.gamedatas.hand = this.gamedatas.hand.filter(c => c.id !== args.predator_card.id);
            }
        }

        // Update hand count
        const countEl = document.getElementById(`wld_hand-count-${args.player_id}`);
        if (countEl) countEl.textContent = parseInt(countEl.textContent) - 1;
    }

    async notif_hunterPlayed(args) {
        // Remove hunter card from hand if it's current player
        if (args.player_id == this.bga.players.getCurrentPlayerId()) {
            this.removeCardFromHand(args.hunter_card.id);
            if (this.gamedatas.hand) {
                this.gamedatas.hand = this.gamedatas.hand.filter(c => c.id !== args.hunter_card.id);
            }
        }

        const countEl = document.getElementById(`wld_hand-count-${args.player_id}`);
        if (countEl) countEl.textContent = parseInt(countEl.textContent) - 1;
    }

    async notif_hunterResolved(args) {
        // Remove all cards that were eliminated
        if (args.removed_cards) {
            args.removed_cards.forEach(card => this.removeCardFromHabitat(card.id));
        }
    }

    async notif_protectorUsed(args) {
        // Protector blocks the hunter - remove protector from hand if it's current player
        if (args.player_id == this.bga.players.getCurrentPlayerId()) {
            this.removeCardFromHand(args.protector_card.id);
            if (this.gamedatas.hand) {
                this.gamedatas.hand = this.gamedatas.hand.filter(c => c.id !== args.protector_card.id);
            }
        }

        // Update hand count
        const countEl = document.getElementById(`wld_hand-count-${args.player_id}`);
        if (countEl) countEl.textContent = parseInt(countEl.textContent) - 1;
    }

    async notif_catastrophePlayed(args) {
        // Remove catastrophe from hand if it's current player
        if (args.player_id == this.bga.players.getCurrentPlayerId()) {
            this.removeCardFromHand(args.catastrophe_card.id);
            if (this.gamedatas.hand) {
                this.gamedatas.hand = this.gamedatas.hand.filter(c => c.id !== args.catastrophe_card.id);
            }
        }

        // Remove all affected cards from habitats
        if (args.removed_cards) {
            args.removed_cards.forEach(entry => {
                const card = entry.card;
                this.removeCardFromHabitat(card.id);
            });
        }

        const countEl = document.getElementById(`wld_hand-count-${args.player_id}`);
        if (countEl) countEl.textContent = parseInt(countEl.textContent) - 1;
    }

    async notif_cycleScored(args) {
        // Update scores
        if (args.playerScores) {
            Object.entries(args.playerScores).forEach(([pid, score]) => {
                // BGA framework handles score display update
            });
        }

        // Show cycle scores
        if (args.scores) {
            Object.entries(args.scores).forEach(([pid, cycleScore]) => {
                const scoreEl = document.getElementById(`wld_habitat-score-${pid}`);
                if (scoreEl) scoreEl.textContent = `+${cycleScore} pts`;
            });
        }
    }

    async notif_rainDiscarded(args) {
        if (args.removed) {
            args.removed.forEach(entry => {
                this.removeCardFromHabitat(entry.card.id);
            });
        }
    }

    async notif_cardsDiscarded(args) {
        // Cards were discarded from hand
        const countEl = document.getElementById(`wld_hand-count-${args.player_id}`);
        if (countEl) countEl.textContent = Math.max(0, parseInt(countEl.textContent) - args.nbr);

        // Remove from UI if it's the current player's hand
        if (args.player_id == this.bga.players.getCurrentPlayerId() && args.card_ids) {
            args.card_ids.forEach(id => {
                this.removeCardFromHand(id);
            });
        }
    }

    async notif_gameEnd(args) {
        // Game is over
    }
}
