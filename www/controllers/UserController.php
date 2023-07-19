<?php


class UserController {
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function list() {
        try {
            $stmt = $this->conn->prepare("SELECT id, email, role FROM users");
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
        $id = $request['id'];
        try {
            $stmt = $this->conn->prepare("SELECT id, email, role FROM users WHERE id = :id");
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
        if(!isset($_SESSION['id'])) {
            return;
        }

        print_r($request);

        $email    = $request['email'];
        $password = password_hash($request['password'], PASSWORD_DEFAULT);
        $id       = $_SESSION['id'];

        try {
            $stmt = $this->conn->prepare("UPDATE users SET email = :email, password = :password WHERE id = :id");

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
        $email    = $request['email'];
        $password = $request['password'];

        try {
            $stmt = $this->conn->prepare("SELECT * FROM users WHERE email = :email");

            $stmt->bindParam(':email', $email);
            
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                $hashedPassword = $result['password'];
                $id             = $result['id'];
                $role           = $result['role'];

                if (password_verify($password, $hashedPassword)) {
                    $session_id = session_id();
                    $_SESSION['id']  = $id;
                    $_SESSION['role'] = $role;
                    setcookie("session_id", $session_id, time() + 3600, "/");
                    return $session_id;
                }
            }

            return false;
        } catch (PDOException $e) {
            error_log("Error: " . $e->getMessage());
        }
    }

    public function resetPassword($request) {
        $email = $request['email'];
        print_r($email);
    }

    public function logout() {
        session_unset();
        session_destroy();
    }
}