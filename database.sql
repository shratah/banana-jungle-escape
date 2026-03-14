CREATE DATABASE IF NOT EXISTS banana_game;

USE banana_game;

CREATE TABLE IF NOT EXISTS users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    current_coins INT(11) DEFAULT 0,
    current_lives INT(11) DEFAULT 3,
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

-- Basic Achievements
INSERT IGNORE INTO achievements (name, description, points) VALUES 
('First Escape', 'Complete the main game for the first time', 50),
('Coin Collector', 'Earn 500 coins in total', 30),
('Puzzle Master', 'Solve 50 puzzles correctly', 100),
('Minigame Pro', 'Clear the memory board 5 times', 40);

