<?php
include 'db.php';

echo "<h2>Running Database Migrations...</h2>";

$queries = [
    "ALTER TABLE users ADD COLUMN google_id VARCHAR(100) DEFAULT NULL AFTER email",
    "ALTER TABLE users ADD COLUMN current_level INT(11) DEFAULT 1 AFTER current_lives",
    "ALTER TABLE users ADD COLUMN theme VARCHAR(20) DEFAULT 'forest'",
    "ALTER TABLE users ADD COLUMN language VARCHAR(10) DEFAULT 'english'",
    "ALTER TABLE users ADD COLUMN powerup_magnet INT(11) DEFAULT 0",
    "ALTER TABLE users ADD COLUMN powerup_freeze INT(11) DEFAULT 0",
    "ALTER TABLE users ADD COLUMN powerup_rainbow INT(11) DEFAULT 0",
    "ALTER TABLE users ADD COLUMN powerup_lucky INT(11) DEFAULT 0",
    "CREATE TABLE IF NOT EXISTS user_giftboxes (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        user_id INT(11) NOT NULL,
        achievement_id INT(11) DEFAULT NULL,
        claimed TINYINT(1) DEFAULT 0,
        reward_coins INT(11) NOT NULL DEFAULT 200,
        claimed_at TIMESTAMP NULL DEFAULT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    "ALTER TABLE user_giftboxes ADD COLUMN IF NOT EXISTS reward_coins INT(11) NOT NULL DEFAULT 200 AFTER claimed",
    "ALTER TABLE user_giftboxes MODIFY achievement_id INT(11) DEFAULT NULL",
    "INSERT IGNORE INTO achievements (name, description, points) VALUES 
        ('First Escape', 'Complete the main game for the first time', 50),
        ('Coin Collector', 'Earn 2000 total coins', 200),
        ('Banana Master', 'Collect 50 bananas', 150),
        ('Speed Demon', 'Achieve 12 combos', 100),
        ('Perfectionist', 'Perfect round for each level (no misses)', 500),
        ('Scholar', '50 correct answers', 100),
        ('Power Player', 'Use all power-ups once', 150),
        ('Puzzle Master', 'Solve 100 puzzles correctly', 100),
        ('Minigame Pro', 'Clear the memory board 5 times', 40)"
];

foreach ($queries as $query) {
    if ($conn->query($query)) {
        echo "<p style='color:green'>Success: " . substr($query, 0, 50) . "...</p>";
    } else {
        echo "<p style='color:orange'>Skipped/Failed: " . $conn->error . " (Query: " . substr($query, 0, 50) . "...)</p>";
    }
}

echo "<h3>Migration Complete! Check <a href='db_check.php'>db_check.php</a></h3>";
?>
