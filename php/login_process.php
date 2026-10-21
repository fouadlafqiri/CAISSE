<?php
require_once __DIR__ . '/session_bootstrap.php';
include('db_connection.php');

if (!isset($_POST['username']) || !isset($_POST['password'])) {
    $_SESSION['error_message'] = "Veuillez remplir tous les champs";
    header("Location: ../index.php");
    exit();
}

$username = strip_tags($_POST['username']);
$password = strip_tags($_POST['password']);

try {
    $sql = 'SELECT * FROM users WHERE username = :username';
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':username', $username, PDO::PARAM_STR);
    $stmt->execute();

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        if (password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = ['owner' => 'admin', 'assistant' => 'server'][$user['role']] ?? $user['role'];
            header("Location: menu.php");
            exit();
        } else {
            $_SESSION['error_message'] = "Mot de passe incorrect";
            header("Location: ../index.php");
            exit();
        }
    } else {
        $_SESSION['error_message'] = "Nom d'utilisateur introuvable";
        header("Location: ../index.php");
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Erreur serveur. Veuillez réessayer plus tard.";
    header("Location: ../index.php");
    exit();
}
