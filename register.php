<?php
session_start();
include "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $email = $_POST["email"];
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("sss", $username, $email, $password);
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