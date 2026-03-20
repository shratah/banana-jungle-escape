<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $updates = [];
    $params = [];
    $types = "";

    $fields = ['coins' => 'current_coins', 'lives' => 'current_lives', 'level' => 'current_level', 
               'theme' => 'theme', 'language' => 'language', 'magnet' => 'powerup_magnet', 
               'freeze' => 'powerup_freeze', 'rainbow' => 'powerup_rainbow', 'lucky' => 'powerup_lucky'];

    foreach ($fields as $post_key => $db_col) {
        if (isset($_POST[$post_key])) {
            $updates[] = "$db_col = ?";
            $params[] = $_POST[$post_key];
            $types .= (is_numeric($_POST[$post_key]) ? "i" : "s");
        }
    }

    if (!empty($updates)) {
        $sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
        $params[] = $user_id;
        $types .= "i";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update stats']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No stats provided']);
    }
} else {
    // Fetch all stats and preferences
    $sql = "SELECT current_coins as coins, current_lives as lives, current_level as level, theme, language, 
                   powerup_magnet as magnet, powerup_freeze as freeze, powerup_rainbow as rainbow, 
                   powerup_lucky as lucky FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    echo json_encode(array_merge(['status' => 'success'], $result));
}
?>
