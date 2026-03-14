<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Update stats
    $coins = isset($_POST['coins']) ? intval($_POST['coins']) : null;
    $lives = isset($_POST['lives']) ? intval($_POST['lives']) : null;

    $updates = [];
    $params = [];
    $types = "";

    if ($coins !== null) {
        $updates[] = "current_coins = ?";
        $params[] = $coins;
        $types .= "i";
    }
    if ($lives !== null) {
        $updates[] = "current_lives = ?";
        $params[] = $lives;
        $types .= "i";
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
    // Fetch stats
    $sql = "SELECT current_coins, current_lives FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    echo json_encode(['status' => 'success', 'coins' => $result['current_coins'], 'lives' => $result['current_lives']]);
}
?>
