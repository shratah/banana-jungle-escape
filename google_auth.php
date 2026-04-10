<?php
session_start();
include 'db.php';

require_once 'vendor/autoload.php'; // Assuming Composer autoload

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['credential'])) {
    $id_token = $_POST['credential'];
    
    try {
        // Get Google's public keys
        $google_keys_url = 'https://www.googleapis.com/oauth2/v3/certs';
        $keys_response = file_get_contents($google_keys_url);
        $keys = json_decode($keys_response, true);
        
        // Decode JWT header to get kid
        $header = json_decode(base64_decode(explode('.', $id_token)[0]), true);
        $kid = $header['kid'];
        
        // Find the matching key
        $public_key = null;
        foreach ($keys['keys'] as $key) {
            if ($key['kid'] === $kid) {
                $public_key = "-----BEGIN CERTIFICATE-----\n" . chunk_split($key['n'], 64, "\n") . "-----END CERTIFICATE-----\n";
                break;
            }
        }
        
        if (!$public_key) {
            throw new Exception('Invalid key ID');
        }
        
        // Verify and decode JWT
        $decoded = JWT::decode($id_token, new Key($public_key, 'RS256'));
        
        // Validate issuer and audience
        if ($decoded->iss !== 'https://accounts.google.com' || !in_array($decoded->aud, ['YOUR_GOOGLE_CLIENT_ID.apps.googleusercontent.com'])) {
            throw new Exception('Invalid token');
        }
        
        $email = $decoded->email;
        $name = $decoded->name ?? explode('@', $email)[0];
        $google_id = $decoded->sub;
        
        // Check if user exists
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
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid token']);
        exit();
    }
}

echo json_encode(['status' => 'error', 'message' => 'Authentication failed']);
?>
