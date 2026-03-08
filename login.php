<?php
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];

                header("Location: index.html");
                exit();
            } else {
                header("Location: login.html?error=incorrect");
                exit();
            }
        } else {
            header("Location: login.html?error=notfound");
            exit();
        }
    } else {
        header("Location: login.html?error=db");
        exit();
    }
} else {
    header("Location: login.html");
    exit();
}
?>