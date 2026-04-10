<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'];

    if ($action == 'buy_powerup') {
        $type = $_POST['type']; // 'magnet', 'freeze', 'rainbow', 'lucky'
        $price = 200; // Fixed price for now

        // Check coins
        $user = $conn->query("SELECT current_coins FROM users WHERE id = $user_id")->fetch_assoc();
        if ($user['current_coins'] >= $price) {
            $col = "powerup_" . $type;
            $conn->query("UPDATE users SET current_coins = current_coins - $price, $col = $col + 1 WHERE id = $user_id");
            echo json_encode(['status' => 'success', 'new_coins' => $user['current_coins'] - $price]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Not enough coins']);
        }
    } 
    elseif ($action == 'buy_achievement') {
        $name = $_POST['name'];
        $price = 500; // Fixed price for achievements

        $user = $conn->query("SELECT current_coins FROM users WHERE id = $user_id")->fetch_assoc();
        if ($user['current_coins'] >= $price) {
            // Check if already owned
            $aid_res = $conn->query("SELECT id FROM achievements WHERE name = '$name'");
            if ($aid_res->num_rows > 0) {
                $aid = $aid_res->fetch_assoc()['id'];
                $owned = $conn->query("SELECT id FROM user_achievements WHERE user_id = $user_id AND achievement_id = $aid");
                if ($owned->num_rows == 0) {
                    $conn->query("UPDATE users SET current_coins = current_coins - $price WHERE id = $user_id");
                    $conn->query("INSERT INTO user_achievements (user_id, achievement_id) VALUES ($user_id, $aid)");
                    echo json_encode(['status' => 'success', 'new_coins' => $user['current_coins'] - $price]);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Already achievement unlocked']);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Achievement not found']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Not enough coins']);
        }
    }
    elseif ($action == 'claim_gift') {
        $gift_id = $_POST['gift_id'];

        // Check if gift belongs to user and not claimed
        $gift = $conn->query("SELECT id, claimed FROM user_giftboxes WHERE id = $gift_id AND user_id = $user_id")->fetch_assoc();
        if ($gift && !$gift['claimed']) {
            $conn->query("UPDATE users SET current_coins = current_coins + 200 WHERE id = $user_id");
            $conn->query("UPDATE user_giftboxes SET claimed = TRUE, claimed_at = NOW() WHERE id = $gift_id");
            $new_coins = $conn->query("SELECT current_coins FROM users WHERE id = $user_id")->fetch_assoc()['current_coins'];
            echo json_encode(['status' => 'success', 'new_coins' => $new_coins]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gift not available']);
        }
    }
}
?>
