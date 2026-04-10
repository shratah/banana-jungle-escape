<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $game_type = $_POST['game_type'];
    $score = isset($_POST['score']) ? intval($_POST['score']) : 0;
    $coins_earned = isset($_POST['coins_earned']) ? intval($_POST['coins_earned']) : 0;
    $time_spent = isset($_POST['time_spent']) ? intval($_POST['time_spent']) : 0;
    $level_completed = isset($_POST['level_completed']) ? intval($_POST['level_completed']) : 0;
    $perfect_level = isset($_POST['perfect_level']) && $_POST['perfect_level'] === 'true';

    // Insert game session
    $sql = "INSERT INTO game_sessions (user_id, game_type, score, coins_earned, time_spent) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isiii", $user_id, $game_type, $score, $coins_earned, $time_spent);

    if ($stmt->execute()) {
        // Update the user's current coin balance with coins earned in this session, including mini-games
        if ($coins_earned > 0) {
            $updateCoinsSql = "UPDATE users SET current_coins = current_coins + ? WHERE id = ?";
            $updateCoinsStmt = $conn->prepare($updateCoinsSql);
            $updateCoinsStmt->bind_param("ii", $coins_earned, $user_id);
            $updateCoinsStmt->execute();
        }

        $response = ['status' => 'success', 'achievements' => [], 'bonus_coins' => 0];

        // Level completion achievements
        if ($game_type == 'main' && $level_completed > 0 && $level_completed <= 10) {
            awardAchievement($conn, $user_id, 'Completed Level ' . $level_completed, $response);
        }

        // Coin achievements - check user's current total coins
        $user_coins = $conn->query("SELECT current_coins FROM users WHERE id = $user_id")->fetch_assoc()['current_coins'];
        if ($user_coins >= 500) awardAchievement($conn, $user_id, 'Collected 500 Coins', $response);
        if ($user_coins >= 1000) awardAchievement($conn, $user_id, 'Collected 1000 Coins', $response);

        // Banana achievements
        $total_bananas = $conn->query("SELECT SUM(score) as total FROM game_sessions WHERE user_id = $user_id AND game_type = 'main'")->fetch_assoc()['total'];
        if ($total_bananas >= 10) awardAchievement($conn, $user_id, 'Collected 10 Bananas', $response);
        if ($total_bananas >= 20) awardAchievement($conn, $user_id, 'Collected 20 Bananas', $response);

        echo json_encode($response);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to save score']);
    }
}

function awardAchievement($conn, $user_id, $name, &$response) {
    // Get achievement ID safely
    $achievement_result = $conn->query("SELECT id FROM achievements WHERE name = '$name' LIMIT 1");
    if ($achievement_result && $achievement_result->num_rows > 0) {
        $achievement_id = $achievement_result->fetch_assoc()['id'];
        
        // Check if already awarded
        $check = $conn->query("SELECT id FROM user_achievements WHERE user_id = $user_id AND achievement_id = $achievement_id");
        if ($check->num_rows == 0) {
            $conn->query("INSERT INTO user_achievements (user_id, achievement_id) VALUES ($user_id, $achievement_id)");
            $conn->query("INSERT INTO user_giftboxes (user_id, achievement_id) VALUES ($user_id, $achievement_id)");
            $response['achievements'][] = $name;
        }
    }
}
?>
