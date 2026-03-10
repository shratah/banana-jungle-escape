<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $game_type = $_POST['game_type']; // 'main' or 'minigame'
    $score = isset($_POST['score']) ? intval($_POST['score']) : 0;
    $coins_earned = isset($_POST['coins_earned']) ? intval($_POST['coins_earned']) : 0;
    $time_spent = isset($_POST['time_spent']) ? intval($_POST['time_spent']) : 0;

    // Insert game session
    $sql = "INSERT INTO game_sessions (user_id, game_type, score, coins_earned, time_spent) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isiii", $user_id, $game_type, $score, $coins_earned, $time_spent);

    if ($stmt->execute()) {
        $response = ['status' => 'success', 'achievements' => []];

        // Check for "First Escape" Achievement
        if ($game_type == 'main' && $score >= 10) {
            $check_sql = "SELECT id FROM user_achievements WHERE user_id = ? AND achievement_id = (SELECT id FROM achievements WHERE name = 'First Escape')";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("i", $user_id);
            $check_stmt->execute();
            if ($check_stmt->get_result()->num_rows == 0) {
                $award_sql = "INSERT INTO user_achievements (user_id, achievement_id) SELECT ?, id FROM achievements WHERE name = 'First Escape'";
                $award_stmt = $conn->prepare($award_sql);
                $award_stmt->bind_param("i", $user_id);
                $award_stmt->execute();
                $response['achievements'][] = 'First Escape';
            }
        }

        // Check for "Coin Collector" Achievement (Total coins >= 500)
        $total_coins_sql = "SELECT SUM(coins_earned) as total FROM game_sessions WHERE user_id = ?";
        $tc_stmt = $conn->prepare($total_coins_sql);
        $tc_stmt->bind_param("i", $user_id);
        $tc_stmt->execute();
        $total_coins = $tc_stmt->get_result()->fetch_assoc()['total'];

        if ($total_coins >= 500) {
            $check_sql = "SELECT id FROM user_achievements WHERE user_id = ? AND achievement_id = (SELECT id FROM achievements WHERE name = 'Coin Collector')";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("i", $user_id);
            $check_stmt->execute();
            if ($check_stmt->get_result()->num_rows == 0) {
                $award_sql = "INSERT INTO user_achievements (user_id, achievement_id) SELECT ?, id FROM achievements WHERE name = 'Coin Collector'";
                $award_stmt = $conn->prepare($award_sql);
                $award_stmt->bind_param("i", $user_id);
                $award_stmt->execute();
                $response['achievements'][] = 'Coin Collector';
            }
        }

        echo json_encode($response);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to save score']);
    }
}
?>
