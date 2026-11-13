CREATE DATABASE IF NOT EXISTS onitama_db;
USE onitama_db;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS friends;
DROP TABLE IF EXISTS games;
DROP TABLE IF EXISTS cards;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    -- Βασικο info
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    api_token VARCHAR(100) NOT NULL UNIQUE, -- απο apps

    -- Στατιστικά για leaderboard
    wins INT NOT NULL DEFAULT 0,
    losses INT NOT NULL DEFAULT 0,
    draws INT NOT NULL DEFAULT 0,
    elo INT NOT NULL DEFAULT 1000,

    -- Listener για online activity
    last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) Engine=InnoDB;


CREATE TABLE cards(
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    starting_player TINYINT NOT NULL
) Engine=InnoDB;

CREATE TABLE card_moves (
    id INT AUTO_INCREMENT PRIMARY KEY,
    card_id INT NOT NULL,
    dx TINYINT NOT NULL, -- Μετατόπιση οριζόντια (-2 έως +2)
    dy TINYINT NOT NULL, -- Μετατόπιση κάθετα (-1 έως +2)

    FOREIGN KEY (card_id) REFERENCES cards(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE friends (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    status ENUM('pending', 'accepted', 'rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_friendship (sender_id, receiver_id)
)Engine=InnoDB;

CREATE TABLE games (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mode ENUM('pvp', 'pvc') NOT NULL DEFAULT 'pvp',

    player1_id INT NOT NULL,
    player2_id INT NULL,
    current_turn TINYINT NOT NULL DEFAULT 1,

    winner_id INT DEFAULT NULL, -- 1 για τον κοκκινο, 2 για τον μπλε, 0 για ισοπαλία, NULL αν δεν έχει τελειώσει το παιχνίδι ακόμα
    win_type ENUM('stone', 'stream', 'timeout', 'forfeit', 'deadlock') DEFAULT NULL,

-- Ολη η παρτίδα εχει 5 καρτες που κανουν cycle around.
    p1_card1_id INT NOT NULL,
    p1_card2_id INT NOT NULL,
    p2_card1_id INT NOT NULL,
    p2_card2_id INT NOT NULL,
    neutral_card_id INT NOT NULL,

    -- Lobby state
    lobby_status ENUM('waiting', 'in_progress', 'finished') NOT NULL DEFAULT 'waiting',
    lobby_created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Timestamps για timeout
    turn_started_at TIMESTAMP NULL,
    turn_created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    turn_updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (player1_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (player2_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (p1_card1_id) REFERENCES cards(id),
    FOREIGN KEY (p1_card2_id) REFERENCES cards(id),
    FOREIGN KEY (p2_card1_id) REFERENCES cards(id),
    FOREIGN KEY (p2_card2_id) REFERENCES cards(id),
    FOREIGN KEY (neutral_card_id) REFERENCES cards(id)
) Engine=InnoDB;

CREATE TABLE game_pieces (
    id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT NOT NULL,
    piece_code VARCHAR(10) NOT NULL, -- 'p1_m', 'p1_s1' (για λόγους 3JS)
    owner TINYINT NOT NULL,          -- 1 ή 2 (κοκκινος ή μπλε)
    type ENUM('master', 'student') NOT NULL,
    x TINYINT NOT NULL,              -- 0 - 4
    y TINYINT NOT NULL,              -- 0 - 4
    is_alive BOOLEAN NOT NULL DEFAULT TRUE,

    FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
    UNIQUE KEY unique_piece_in_game (game_id, piece_code)
) ENGINE=InnoDB;