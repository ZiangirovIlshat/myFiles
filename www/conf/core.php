<?php

// подключение к бд
require_once (__DIR__ . DIRECTORY_SEPARATOR . "database.php");

$database = new Database();
$db = $database->getConnection();

$standardAdminMail = 'admin@cloud.local';
$standardAdminPassword = 'admin';

class Core {
    private $standardAdminMail;
    private $standardAdminPassword;
    private $conn;

    public function __construct($standardAdminMail, $standardAdminPassword, $db) {
        $this->standardAdminMail = $standardAdminMail;
        $this->standardAdminPassword= $standardAdminPassword;
        $this->conn = $db;
    }

    public function createUsersTable() {
        try {
            $createTable = $this->conn->prepare(
                "CREATE TABLE IF NOT EXISTS users(
                    id INT(11) NOT NULL AUTO_INCREMENT,
                    email VARCHAR(32) NOT NULL,
                    password VARCHAR(255),
                    role TINYINT(1) NOT NULL DEFAULT 0,
                    hash VARCHAR(32),
                    PRIMARY KEY (id)
                )ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1"
            );
            $createTable->execute();
        } catch(PDOException $e) {
            error_log("Error: " . $e->getMessage());
        }
    }

    public function createFilesTable() {
        try {
            $createTable = $this->conn->prepare(
                "CREATE TABLE IF NOT EXISTS files(
                    id INT(11) AUTO_INCREMENT PRIMARY KEY,
                    file_name VARCHAR(255),
                    file_path VARCHAR(255),
                    owner_id INT(11),
                    FOREIGN KEY (owner_id) REFERENCES users(id)
                )ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1"
            );
            $createTable->execute();
        } catch(PDOException $e) {
            error_log("Error: " . $e->getMessage());
        }
    }

    public function createAccessTable() {
        try {
            $createTable = $this->conn->prepare(
                "CREATE TABLE access (
                    id INT(11) AUTO_INCREMENT PRIMARY KEY,
                    file_id INT(255),
                    user_id INT(255),
                    FOREIGN KEY (file_id) REFERENCES files(id),
                    FOREIGN KEY (user_id) REFERENCES users(id)
                )ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1"
            );
            $createTable->execute();
        } catch(PDOException $e) {
            error_log("Error: " . $e->getMessage());
        }
    }

    public function createAdminUser() {
        try {
            $checkUsers = $this->conn->prepare("SELECT COUNT(*) FROM users");
            $checkUsers->execute();
            $userCount = $checkUsers->fetchColumn();

            if ($userCount === 0) {

                $email    = $this->standardAdminMail;
                $password = password_hash($this->standardAdminPassword, PASSWORD_DEFAULT);
                $role     = 1;
                $hash     = '';

                $createAdmin = $this->conn->prepare("INSERT INTO users (email, password, role, hash) VALUES (:email, :password, :role, :hash)");
                $createAdmin->bindParam(':email', $email);
                $createAdmin->bindParam(':password', $password);
                $createAdmin->bindParam(':role', $role);
                $createAdmin->bindParam(':hash', $hash);
                $createAdmin->execute();
            }
        } catch(PDOException $e) {
            error_log("Error: " . $e->getMessage());
        }
    }
}

$core = new Core($standardAdminMail, $standardAdminPassword, $db);
$core->createUsersTable();
$core->createFilesTable();
$core->createAccessTable();
$core->createAdminUser();