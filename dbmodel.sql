
-- ------
-- BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
-- WildLife implementation
--
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-- -----

-- Standard BGA player table
ALTER TABLE `player` ADD `player_mulligan_status` TINYINT UNSIGNED NOT NULL DEFAULT 0;

-- Card table used with the Deck component
CREATE TABLE IF NOT EXISTS `card` (
  `card_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `card_type` VARCHAR(24) NOT NULL,
  `card_type_arg` INT NOT NULL,
  `card_location` VARCHAR(24) NOT NULL,
  `card_location_arg` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`card_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=1;

-- Track per-cycle scores for tiebreaker
CREATE TABLE IF NOT EXISTS `cycle_score` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `player_id` INT UNSIGNED NOT NULL,
  `cycle_num` INT UNSIGNED NOT NULL,
  `score` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=1;
