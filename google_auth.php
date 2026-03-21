<?php
session_start();
include 'db.php';

// This is a simplified Google Auth handler.
// In a real production app, you MUST verify the 'credential' (JWT) using the Google API Client Library
// or a library like 'firebase/php-jwt'.

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['credential'])) {
    $id_token = $_POST['credential'];
    
    // For this demonstration/project, we will decod the JWT to get the email and name.
    // NOTE: Decoding without verification is NOT secure for production.
    $parts = explode('.', $id_token);
    if (count($parts) === 3) {
        $payload = json_decode(base64_decode($parts[1]), true);
        
        if ($payload && isset($payload['email'])) {
            $email = $payload['email'];
            $name = $payload['name'] ?? explode('@', $email)[0];
            $google_id = $payload['sub'];

            // Check if user exists (by email or google_id)
            $stmt = $conn->prepare("SELECT id, username FROM users WHERE email = ? OR google_id = ?");
            $stmt->bind_param("ss", $email, $google_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                $user_id = $user['id'];
                $username = $user['username'];
            } else {
                // Register new user
                $stmt = $conn->prepare("INSERT INTO users (username, email, google_id, password) VALUES (?, ?, ?, 'GOOGLE_AUTH')");
                $stmt->bind_param("sss", $name, $email, $google_id);
                $stmt->execute();
                $user_id = $conn->insert_id;
                $username = $name;
            }

            // Set session
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;

            echo json_encode(['status' => 'success']);
            exit();
        }
    }
}

echo json_encode(['status' => 'error', 'message' => 'Authentication failed']);
?>
