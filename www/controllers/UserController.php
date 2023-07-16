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
            error_log("Error retrieving the list of users: " . $e->getMessage(), 0);
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
            error_log("Error updating user data: " . $e->getMessage());
            return false;
        }
    }
    
    public function update($request) {
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
            error_log("Error updating user data: " . $e->getMessage());
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

            if($stmt->rowCount() != 1) {
                return "Неверный логин или пароль";
            }

            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $id = $data[0]['id'];
            $role = $data[0]['role'];
            $session_id = session_id();
            $_SESSION['id']  = $id;
            $_SESSION['role'] = $role;
            setcookie("session_id", $session_id, time() + 3600, "/");
            return $session_id;

        } catch (PDOException $e) {
            error_log("Error: " . $e->getMessage());
        }
    }

    public function resetPassword($email) {
        // TODO:
    }

    public function logout() {
        session_unset();
        session_destroy();
    }
}