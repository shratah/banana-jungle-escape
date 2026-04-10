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
    achievement_id INT(11) NOT NULL,
    claimed BOOLEAN DEFAULT FALSE,
    claimed_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE
);

-- Achievements
INSERT IGNORE INTO achievements (name, description, points) VALUES 
('Completed Level 1', 'Complete Level 1', 50),
('Completed Level 2', 'Complete Level 2', 50),
('Completed Level 3', 'Complete Level 3', 50),
('Completed Level 4', 'Complete Level 4', 50),
('Completed Level 5', 'Complete Level 5', 50),
('Completed Level 6', 'Complete Level 6', 50),
('Completed Level 7', 'Complete Level 7', 50),
('Completed Level 8', 'Complete Level 8', 50),
('Completed Level 9', 'Complete Level 9', 50),
('Completed Level 10', 'Complete Level 10', 50),
('Collected 500 Coins', 'Collect 500 total coins', 100),
('Collected 1000 Coins', 'Collect 1000 total coins', 200),
('Collected 10 Bananas', 'Collect 10 bananas', 50),
('Collected 20 Bananas', 'Collect 20 bananas', 100),
('Banana Master', 'Collect 35 bananas', 150),
('Banana King', 'Collect 50 bananas', 250);

