<?php
$host = "localhost";
$user = "root";
$pass = "";
$db = "banana_game";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$createGiftboxesTable = "CREATE TABLE IF NOT EXISTS user_giftboxes (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    achievement_id INT(11) NOT NULL,
    claimed BOOLEAN DEFAULT FALSE,
    claimed_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE
)";

if (!$conn->query($createGiftboxesTable)) {
    error_log('Failed to ensure user_giftboxes table exists: ' . $conn->error);
}

$desiredAchievements = [
    ['Completed Level 1', 'Complete Level 1', 50],
    ['Completed Level 2', 'Complete Level 2', 50],
    ['Completed Level 3', 'Complete Level 3', 50],
    ['Completed Level 4', 'Complete Level 4', 50],
    ['Completed Level 5', 'Complete Level 5', 50],
    ['Completed Level 6', 'Complete Level 6', 50],
    ['Completed Level 7', 'Complete Level 7', 50],
    ['Completed Level 8', 'Complete Level 8', 50],
    ['Completed Level 9', 'Complete Level 9', 50],
    ['Completed Level 10', 'Complete Level 10', 50],
    ['Collected 500 Coins', 'Collect 500 total coins', 100],
    ['Collected 1000 Coins', 'Collect 1000 total coins', 200],
    ['Collected 10 Bananas', 'Collect 10 bananas', 50],
    ['Collected 20 Bananas', 'Collect 20 bananas', 100]
];

$insertAchievement = $conn->prepare("INSERT INTO achievements (name, description, points) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE description = VALUES(description), points = VALUES(points)");
foreach ($desiredAchievements as $achievement) {
    $insertAchievement->bind_param('ssi', $achievement[0], $achievement[1], $achievement[2]);
    $insertAchievement->execute();
}

$allowedNames = array_map(function ($achievement) use ($conn) {
    return "'" . $conn->real_escape_string($achievement[0]) . "'";
}, $desiredAchievements);
$deleteOldAchievements = "DELETE FROM achievements WHERE name NOT IN (" . implode(',', $allowedNames) . ")";
if (!$conn->query($deleteOldAchievements)) {
    error_log('Failed to remove outdated achievements: ' . $conn->error);
}

// Clean up duplicate achievements, keeping the one with the lowest ID
$cleanupDuplicates = "
DELETE a1 FROM achievements a1
INNER JOIN achievements a2 
WHERE a1.id > a2.id AND a1.name = a2.name
";
if (!$conn->query($cleanupDuplicates)) {
    error_log('Failed to clean up duplicate achievements: ' . $conn->error);
}
?>