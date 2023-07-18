<?php

// подключение к бд
require_once ("database.php");

$database = new Database();
$db = $database->getConnection();

try {
    $standartEmail = 'admin@admin.cloud';
    $standartPassword = password_hash('Admin', PASSWORD_DEFAULT);

    $sql = "CREATE TABLE IF NOT EXISTS Users(
        id int(11) NOT NULL AUTO_INCREMENT,
        email varchar(32) NOT NULL,
        password VARCHAR(255),
        PRIMARY KEY (id)
    )ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=65";

    $stmt = $db->prepare($sql);
    $stmt->execute();

    $sql = "SELECT COUNT(*) FROM Users";
    $result = $db->query($sql);
    $rowCount = $result->fetchColumn();

    if ($rowCount === 0) {
        $sql = "INSERT INTO Users (email, password) VALUES (:email, :password)";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':email', $standartEmail);
        $stmt->bindParam(':password', $standartPassword);
        $stmt->execute();
    }
}

catch (PDOException $e) {
    error_log("Error: " . $e->getMessage());
}
