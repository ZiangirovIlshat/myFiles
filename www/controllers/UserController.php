<?php


class UserController {
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function list() {
        try {
            $stmt = $this->conn->prepare("SELECT id, email, role FROM User");
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($users);
        } catch(PDOException $e) {
            error_log("Ошибка при извлечении списка пользователей: " . $e->getMessage(), 0);
            return array();
        }
    }

    public function getUser($request) {
        $id = $request[0];
        try {
            $stmt = $this->conn->prepare("SELECT id, email, role FROM user WHERE id = :id");
            $stmt->bindParam(":id", $id);
            $stmt->execute();

            $user = $stmt->fetchAll(PDO::FETCH_ASSOC);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($user);
        } catch(PDOException $e) {
            error_log("Ошибка при обновлении пользовательских данных: " . $e->getMessage());
            return false;
        }
    }
    
    public function update($request) {
        if(!isset($_SESSION['id'])) {
            return "Пользователь не авторизован";
        }

        $email = $request['email'];
        $password = $request['password'];
        $id = $_SESSION['id'];

        try {
            $stmt = $this->conn->prepare("UPDATE user SET email = :email, password = :password WHERE id = :id");

            $stmt->bindParam(":id", $id);
            $stmt->bindParam(":email", $email);
            $stmt->bindParam(":password", $password);

            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                return true;
            } else {
                return false;
            }
        } catch (PDOException $e) {
            error_log("Ошибка при обновлении пользовательских данных: " . $e->getMessage());
            return false;
        }
    }
    
    public function login($request) {
        $email = $request['email'];
        $password = $request['password'];

        try {
            $stmt = $this->conn->prepare("SELECT * FROM user WHERE email = :email AND password = :password");

            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $password);
            
            $stmt->execute();

            if ($stmt->rowCount() == 1) {
                $id = $stmt->fetchAll(PDO::FETCH_ASSOC)[0]['id'];
                $session_id = session_id();
                $_SESSION['id'] = $id;
                setcookie("session_id", $session_id, time() + 3600, "/");
                return $session_id;
            } else {
                return "Неверный логин или пароль";
            }
        } catch (PDOException $e) {
            error_log("Error: " . $e->getMessage());
        }
    }

    public function resetPassword($email) {
        TODO:
    }

    public function logout() {
        session_unset();
        session_destroy();
    }
}