<?php
session_start();
include "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $password_confirm = $_POST["password_confirm"];

    if ($password !== $password_confirm) {
        header("Location: register.html?error=password_mismatch");
        exit();
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("sss", $username, $email, $hashedPassword);
        if ($stmt->execute()) {
            header("Location: login.html?msg=registered");
            exit();
        } else {
            header("Location: register.html?error=exists");
            exit();
        }
    } else {
        header("Location: register.html?error=db");
        exit();
    }
} else {
    header("Location: register.html");
    exit();
}
?>