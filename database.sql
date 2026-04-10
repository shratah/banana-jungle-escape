CREATE DATABASE IF NOT EXISTS banana_game;

USE banana_game;

CREATE TABLE IF NOT EXISTS users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    google_id VARCHAR(100) DEFAULT NULL,
    password VARCHAR(255) NOT NULL,
    current_coins INT(11) DEFAULT 0,
    current_lives INT(11) DEFAULT 3,
    current_level INT(11) DEFAULT 1,
    theme VARCHAR(20) DEFAULT 'forest',
    language VARCHAR(10) DEFAULT 'english',
    powerup_magnet INT(11) DEFAULT 0,
    powerup_freeze INT(11) DEFAULT 0,
    powerup_rainbow INT(11) DEFAULT 0,
    powerup_lucky INT(11) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS game_sessions (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    game_type ENUM('main', 'minigame') NOT NULL,
    score INT(11) DEFAULT 0,
    coins_earned INT(11) DEFAULT 0,
    time_spent INT(11) DEFAULT 0, -- in seconds
    played_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS achievements (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    points INT(11) DEFAULT 0
);

CREATE TABLE IF NOT EXISTS user_achievements (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    achievement_id INT(11) NOT NULL,
    earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS user_giftboxes (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    reward_coins INT(11) NOT NULL DEFAULT 200,
    claimed TINYINT(1) DEFAULT 0,
    claimed_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Achievements
INSERT IGNORE INTO achievements (name, description, points) VALUES 
('Level 1', 'Reach level 1', 10),
('Level 2', 'Reach level 2', 20),
('Level 3', 'Reach level 3', 30),
('Level 4', 'Reach level 4', 40),
('Level 5', 'Reach level 5', 50),
('Level 6', 'Reach level 6', 60),
('Level 7', 'Reach level 7', 70),
('Level 8', 'Reach level 8', 80),
('Level 9', 'Reach level 9', 90),
('Level 10', 'Reach level 10', 100),
('500 Coins', 'Collect 500 coins', 50),
('1000 Coins', 'Collect 1000 coins', 100),
('10 Bananas', 'Collect 10 bananas in minigame', 25),
('20 Bananas', 'Collect 20 bananas in minigame', 50);

