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
            error_log("Error: " . $e->getMessage(), 0);
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
            error_log("Error: " . $e->getMessage());
            return false;
        }
    }
    
    public function update($request) {
        if(!isset($_SESSION['id'])) {
            return;
        }

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
            error_log("Error: " . $e->getMessage());
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
                    $session_id       = session_id();
                    $_SESSION['id']   = $id;
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

        try {
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                $hash = urlencode($email) . rand(1000, 9999);

                $updateStmt = $this->conn->prepare("UPDATE users SET hash = :hash WHERE email = :email");
                $updateStmt->bindParam(':hash', $hash, PDO::PARAM_STR);
                $updateStmt->bindParam(':email', $email, PDO::PARAM_STR);
                $updateStmt->execute();
                
                $resetLink = "http://cloud-storage.local/users/reset_password_hash/" . $email . "/" . $hash;
                $subject   = "Сброс пароля";
                $message   = "Для сброса пароля перейдите по следующей ссылке: ". $resetLink;
                $headers   = "From: your_email@example.com";

                print_r($resetLink);
                
                mail($email, $subject, $message, $headers);
            } else {
                return false;
            }
        } catch(PDOException $e) {
            error_log("Error: " . $e->getMessage());
        }
    }

    public function resetPasswordHash($request) {
        $email = $request['email'];
        $hash  = $request['hash'];
        function generateRandomPassword($length = 8)
        {
            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $password = '';
        
            for ($i = 0; $i < $length; $i++) {
                $index = rand(0, strlen($characters) - 1);
                $password .= $characters[$index];
            }
        
            return $password;
        }
        try {
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM users WHERE email = :email AND hash = :hash");
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->bindParam(':hash', $hash, PDO::PARAM_STR);
            $stmt->execute();

            $count = $stmt->fetchColumn();

            if ($count > 0) {
                $newPassword = generateRandomPassword();

                $updateStmt = $this->conn->prepare("UPDATE users SET password = :password, hash = '' WHERE email = :email AND hash = :hash");
                $updateStmt->bindParam(':password', $newPassword, PDO::PARAM_STR);
                $updateStmt->bindParam(':email', $email, PDO::PARAM_STR);
                $updateStmt->bindParam(':hash', $hash, PDO::PARAM_STR);
                $updateStmt->execute();

                echo($newPassword);
            } else {
                return false;
            }
        } catch(PDOException $e) {
            error_log("Error: " . $e->getMessage());
        }
    }

    public function logout() {
        session_unset();
        session_destroy();
    }
}