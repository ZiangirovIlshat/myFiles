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

    public function getUser($request) {
        if(!isset($request['id'])) {
            error_log("Error: Not all information was provided");
            throw new Exception("Ошибка при получении данных");
        }

        $id = $request['id'];
        try {
            $stmt = $this->conn->prepare("SELECT id, email, role FROM users WHERE id = :id");
            $stmt->bindParam(":id", $id);
            $stmt->execute();
        } catch(PDOException $e) {
            error_log("Error: " . $e->getMessage());
            throw new Exception("Ошибка подключения к базе данных");
        }

        $user = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if($user === []) {
            throw new Exception("Пользователь не найден");
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($user);
    }

    public function create($request) {
        if(!isset($request['email']) || !isset($request['password'])) {
            error_log("Error: Not all information was provided");
            throw new Exception("Ошибка при получении данных");
        }

        $email    = $request['email'];
        $password = $request['password'];
        $role     = 0;

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Некорректный адрес электронной почты");
        }
        
        try {
            $checEmail = $this->conn->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
            $checEmail->bindParam(':email', $email, PDO::PARAM_STR);
            $checEmail->execute();
    
            $count = $checEmail->fetchColumn();
        } catch(PDOException $e) {
            error_log("Error: " . $e->getMessage(), 0);
            throw new Exception("Ошибка подключения к базе данных");
        }
    
        if ($count > 0) {
            throw new Exception("Аккаунт с указанной почтой уже существует");
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
            $addUser = $this->conn->prepare("INSERT INTO users (email, password, role) VALUES (:email, :password, :role)");
            $addUser->bindParam(':email', $email, PDO::PARAM_STR);
            $addUser->bindParam(':password', $password, PDO::PARAM_STR);
            $addUser->bindParam(':role', $role, PDO::PARAM_STR);

            $addUser->execute();
        } catch(PDOException $e) {
            error_log("Error: " . $e->getMessage(), 0);
            throw new Exception("Ошибка подключения к базе данных");
        }

        $userId = $this->conn->lastInsertId();
        $root   = "BASE_ROOT";

        try {
            $addUserRootDirectory = $this->conn->prepare("INSERT INTO directories (directories_path, owner_id) VALUES (:directories_path, :owner_id)");
            $addUserRootDirectory->bindParam(':directories_path', $root, PDO::PARAM_STR);
            $addUserRootDirectory->bindParam(':owner_id', $userId, PDO::PARAM_STR);

            $addUserRootDirectory->execute();
        } catch(PDOException $e) {
            error_log("Error: " . $e->getMessage(), 0);
            print_r('da');
            throw new Exception("Ошибка подключения к базе данных");
        }
    }
    
    public function update($request) {
        if(!isset($_SESSION['user_id'])) {
            return;
        }

        if(!isset($request['email']) || !isset($request['password'])) {
            error_log("Error: " . "Not all data was specified when trying to update a user");
            throw new Exception("Ошибка при получении данных");
        }

        $email    = $request['email'];
        $password = $request['password'];
        $id       = $_SESSION['user_id'];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Некорректный адрес электронной почты");
        }

        try {
            $checEmail = $this->conn->prepare("SELECT COUNT(*) FROM users WHERE email = :newEmail AND id != :userId");
            $checEmail->bindParam(':newEmail', $email, PDO::PARAM_STR);
            $checEmail->bindParam(':userId', $id, PDO::PARAM_INT);
            $checEmail->execute();
    
            $count = $checEmail->fetchColumn();
        } catch(PDOException $e) {
            error_log("Error: " . $e->getMessage());
            throw new Exception("Ошибка подключения к базе данных");
        }
    
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

        try {
            $updateStmt = $this->conn->prepare("UPDATE users SET email = :newEmail, password= :newPassword WHERE id = :userId");
            $updateStmt->bindParam(':newEmail', $email, PDO::PARAM_STR);
            $updateStmt->bindParam(':newPassword', $password, PDO::PARAM_STR);
            $updateStmt->bindParam(':userId', $id, PDO::PARAM_INT);

            $updateStmt->execute();
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
        } catch (PDOException $e) {
            error_log("Error: " . $e->getMessage());
            throw new Exception("Ошибка подключения к базе данных: " . $e->getMessage());
        }

        if (!$result) {
            throw new Exception("Не найден пользователь с указанной почтой");
        }

        $hashedPassword = $result['password'];
        $id             = $result['id'];
        $email          = $result['email'];
        $role           = $result['role'];

        if (!password_verify($password, $hashedPassword)) {
            throw new Exception("Не верный пароль");
        }

        $session_id             = session_id();
        $_SESSION['user_id']    = $id;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_role']  = $role;

        setcookie("session_id", $session_id, time() + 86400, "/");
    }

    public function resetPassword($request) {
        if(!isset($request['email'])) {
            error_log("Error: " . "Not all data was specified when trying to update a user");
            throw new Exception("Ошибка при получении данных");
        }

        $email = $request['email'];
        try {
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
        } catch(PDOException $e) {
            error_log("Error: " . $e->getMessage());
            throw new Exception("Ошибка подключения к базе данных: " . $e->getMessage());
        }

        $count = $stmt->fetchColumn();
            
        if ($count === 0) {
            throw new Exception("Не найден пользователь с указанным адресом электронной почты");
        }

        $hash = urlencode($email) . rand(1000, 9999);

        try {
            $updateStmt = $this->conn->prepare("UPDATE users SET hash = :hash WHERE email = :email");
            $updateStmt->bindParam(':hash', $hash, PDO::PARAM_STR);
            $updateStmt->bindParam(':email', $email, PDO::PARAM_STR);
            $updateStmt->execute();
        } catch(PDOException $e) {
            error_log("Error: " . $e->getMessage());
            throw new Exception("Ошибка подключения к базе данных: " . $e->getMessage());
        }
                
        $resetLink = "http://cloud-storage.local/users/reset_password_hash/" . $email . "/" . $hash;
        $subject   = "Сброс пароля";
        $message   = "Для сброса пароля перейдите по следующей ссылке: ". $resetLink;
        $headers   = "From: your_email@example.com";
                
        mail($email, $subject, $message, $headers);
    }

    public function resetPasswordLink($request) {
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
        } catch(PDOException $e) {
            error_log("Error: " . $e->getMessage());
            throw new Exception("Ошибка подключения к базе данных: " . $e->getMessage());
        }

        $count = $stmt->fetchColumn();

        if ($count === 0) {
            return false;
        }

        $newPassword = generateRandomPassword();

        try {
            $updateStmt = $this->conn->prepare("UPDATE users SET password = :password, hash = '' WHERE email = :email AND hash = :hash");
            $updateStmt->bindParam(':password', $newPassword, PDO::PARAM_STR);
            $updateStmt->bindParam(':email', $email, PDO::PARAM_STR);
            $updateStmt->bindParam(':hash', $hash, PDO::PARAM_STR);
            $updateStmt->execute();
        } catch(PDOException $e) {
            error_log("Error: " . $e->getMessage());
            throw new Exception("Ошибка подключения к базе данных: " . $e->getMessage());
        }

        echo($newPassword);
    }

    public function search($request) {
        if(!isset($request['email'])) {
            error_log("Error: Not all information was provided");
            throw new Exception("Ошибка при получении данных");
        }

        $email = $request['email'];

        try {
            $stmt = $this->conn->prepare("SELECT id, email, role FROM users WHERE email = :email");
            $stmt->bindParam(":email", $email);
            $stmt->execute();
        } catch(PDOException $e) {
            error_log("Error: " . $e->getMessage());
            throw new Exception("Ошибка подключения к базе данных");
        }

        $user = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if($user === []) {
            throw new Exception("Пользователь не найден");
        }

        return $user[0]['id'];
    }

    public function logout() {
        session_unset();
        session_destroy();
    }
}