<?php

// подключение к бд
require_once ("database.php");

$database = new Database();
$db = $database->getConnection();

try {
    $sql = "CREATE TABLE IF NOT EXISTS users(
        id INT(11) NOT NULL AUTO_INCREMENT,
        email VARCHAR(32) NOT NULL,
        password VARCHAR(255),
        role TINYINT(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (id)
    )ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1";

    $stmt = $db->prepare($sql);
    $stmt->execute();

    $sql = "SELECT COUNT(*) FROM users";
    $result = $db->query($sql);
    $rowCount = $result->fetchColumn();

    if ($rowCount === 0) {
        $standartEmail = 'admin@admin.cloud';
        $standartPassword = password_hash('Admin', PASSWORD_DEFAULT);
        $role = 1;

        $sql = "INSERT INTO users (email, password, role) VALUES (:email, :password, :role)";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':email', $standartEmail);
        $stmt->bindParam(':password', $standartPassword);
        $stmt->bindParam(':role', $role);
        $stmt->execute();
    }
}

catch (PDOException $e) {
    error_log("Error: " . $e->getMessage());
}
