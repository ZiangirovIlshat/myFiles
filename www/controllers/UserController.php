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
            throw new Exception("Ошибка подключения к базе данных: " . $e->getMessage());
        }
    }

    public function create($request) {
        if(!isset($request['email']) || !isset($request['password'])) {
            error_log("Error: " . "Not all data was specified when trying to register a user");
            throw new Exception("Ошибка при получении данных");
        }

        $email    = $request['email'];
        $password = $request['password'];
        $role     = 0;

        {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Некорректный адрес электронной почты");
            }
        
            try {
                $checkStmt = $this->conn->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
                $checkStmt->bindParam(':email', $email, PDO::PARAM_STR);
                $checkStmt->execute();
        
                $count = $checkStmt->fetchColumn();
        
                if ($count > 0) {
                    throw new Exception("Аккаунт с указанной почтой уже существует");
                }
            } catch(PDOException $e) {
                error_log("Error: " . $e->getMessage(), 0);
                throw new Exception("Ошибка подключения к базе данных");
            }

            $passwordLength = strlen($password);
            if ($passwordLength < 8) {
                throw new Exception("Пароль должен содержать минимум 8 символов");
            }
            if($passwordLength > 30) {
                throw new Exception("Длинна пароля не должна превышать 30 символов");
            }
        
            $password = password_hash($password, PASSWORD_DEFAULT);

            try {
                $addStmt = $this->conn->prepare("INSERT INTO users (email, password, role) VALUES (:email, :password, :role)");
                $addStmt->bindParam(':email', $email, PDO::PARAM_STR);
                $addStmt->bindParam(':password', $password, PDO::PARAM_STR);
                $addStmt->bindParam(':role', $role, PDO::PARAM_STR);
                $addStmt->execute();
        
                return true;
            } catch(PDOException $e) {
                error_log("Error: " . $e->getMessage(), 0);
                throw new Exception("Ошибка подключения к базе данных");
            }
        }
    }

    public function getUser($request) {
        $id = $request['id'];
        try {
            $stmt = $this->conn->prepare("SELECT id, email, role FROM users WHERE id = :id");
            $stmt->bindParam(":id", $id);
            $stmt->execute();

            $user = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if($user === []) {
                throw new Exception("Пользователь не найден");
            }
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($user);
        } catch(PDOException $e) {
            error_log("Error: " . $e->getMessage());
            throw new Exception("Ошибка подключения к базе данных");
        }
    }
    
    public function update($request) {
        if(!isset($_SESSION['id'])) {
            return;
        }

        if(!isset($request['email']) || !isset($request['password'])) {
            error_log("Error: " . "Not all data was specified when trying to update a user");
            throw new Exception("Ошибка при получении данных");
        }

        $email    = $request['email'];
        $password = $request['password'];
        $id       = $_SESSION['id'];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Некорректный адрес электронной почты");
        }

        try {
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM users WHERE email = :newEmail AND id != :userId");
            $stmt->bindParam(':newEmail', $email, PDO::PARAM_STR);
            $stmt->bindParam(':userId', $id, PDO::PARAM_INT);
            $stmt->execute();
    
            $count = $stmt->fetchColumn();
    
            if ($count > 0) {
                throw new Exception("Пользователь с указанной почтой уже существует");
            }

            $passwordLength = strlen($password);
            if ($passwordLength < 8) {
                throw new Exception("Пароль должен содержать минимум 8 символов");
            }
            if($passwordLength > 30) {
                throw new Exception("Длинна пароля не должна превышать 30 символов");
            }
        
            $password = password_hash($password, PASSWORD_DEFAULT);

            $updateStmt = $this->conn->prepare("UPDATE users SET email = :newEmail, password= :newPassword WHERE id = :userId");
            $updateStmt->bindParam(':newEmail', $email, PDO::PARAM_STR);
            $updateStmt->bindParam(':newPassword', $password, PDO::PARAM_STR);
            $updateStmt->bindParam(':userId', $id, PDO::PARAM_INT);
            $updateStmt->execute();

            return true;
        } catch(PDOException $e) {
            error_log("Error: " . $e->getMessage());
            throw new Exception("Ошибка подключения к базе данных");
        }
    }
    
    public function login($request) {
        if(!isset($request['email']) || !isset($request['password'])) {
            error_log("Error: " . "Not all data was specified when trying to update a user");
            throw new Exception("Ошибка при получении данных");
        }

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

            throw new Exception("Не найден пользователь с данным паролем или почтой");
        } catch (PDOException $e) {
            error_log("Error: " . $e->getMessage());
            throw new Exception("Ошибка подключения к базе данных: " . $e->getMessage());
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
                throw new Exception("Не найден пользователь с указанным адресом электронной почты");
            }
        } catch(PDOException $e) {
            error_log("Error: " . $e->getMessage());
            throw new Exception("Ошибка подключения к базе данных: " . $e->getMessage());
        }
    }

    public function resetPasswordHash($request) {
        $email = $request['email'];
        $hash  = $request['hash'];
        function generateRandomPassword($length = 10)
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
            throw new Exception("Ошибка подключения к базе данных: " . $e->getMessage());
        }
    }

    public function logout() {
        session_unset();
        session_destroy();
    }
}