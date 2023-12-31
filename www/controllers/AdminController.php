<?php


class AdminController {
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;

        if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 1) {
            http_response_code(401);
            throw new Exception ("Недостаточно прав!");
        }
    }

    public function list(){
        try {
            $stmt = $this->conn->prepare("SELECT id, email, role FROM users");
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($users);
        } catch(PDOException $e) {
            error_log("Error retrieving the list of users:" . $e->getMessage(), 0);
            throw new Exception("Ошибка при получении иформации о пользователях");
        }
    }

    public function getUser($request){
        if(!isset($request['id'])) {
            error_log("Error: Not all information was provided");
            throw new Exception("Ошибка при получении данных");
        }

        $id = $request['id'];

        try {
            $stmt = $this->conn->prepare("SELECT id, email, role, password FROM users WHERE id = :id");
            $stmt->bindParam(":id", $id);
            $stmt->execute();
        } catch(PDOException $e) {
            error_log("Error updating user data: " . $e->getMessage());
            throw new Exception("Ошибка при получении иформации о пользователе");
        }

        $user = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if($user === []) {
            throw new Exception("Пользователь не найден");
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($user);
    }

    public function deleteUser($request){
        if(!isset($request['id'])) {
            error_log("Error: Not all information was provided");
            throw new Exception("Ошибка при получении данных");
        }

        $id = $request['id'];

        try {
            $deleteUsersFiles = $this->conn->prepare("DELETE FROM files WHERE owner_id = :id");
            $deleteUsersFiles->bindParam(":id", $id);
            $deleteUsersFiles->execute();

            $deleteUser = $this->conn->prepare("DELETE FROM users WHERE id = :id");
            $deleteUser->bindParam(":id", $id);
            $deleteUser->execute();
        } catch(PDOException $e) {
            error_log("Error updating user data: " . $e->getMessage());
            throw new Exception("Ошибка при удалении пользователя");
        }
    }

    public function update($request){
        if(!isset($request['email']) || !isset($request['password']) || !isset($request['id'])) {
            error_log("Error: " . "Not all data was specified when trying to update a user");
            throw new Exception("Ошибка при получении данных");
        }

        $id       = $request['id'];
        $email    = $request['email'];
        $password = $request['password'];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Некорректный адрес электронной почты");
        }

        try {
            $checkID = $this->conn->prepare("SELECT COUNT(*) FROM users WHERE id = :userId");
            $checkID->bindParam(':userId', $id, PDO::PARAM_INT);
            $checkID->execute();

            $count = $checkID->fetchColumn();
        } catch(PDOException $e) {
            error_log("Error: " . $e->getMessage());
            throw new Exception("Ошибка при поиске пользователя");
        }

        if ($count === 0) {
            throw new Exception("Пользователь с данным ID не существует");
        }

        try {
            $checkEmail = $this->conn->prepare("SELECT COUNT(*) FROM users WHERE email = :newEmail AND id != :userId");
            $checkEmail->bindParam(':newEmail', $email, PDO::PARAM_STR);
            $checkEmail->bindParam(':userId', $id, PDO::PARAM_INT);
            $checkEmail->execute();
        } catch(PDOException $e) {
            error_log("Error: " . $e->getMessage());
            throw new Exception("Ошибка при проверке уникальности почты");
        }

        $count = $checkEmail->fetchColumn();
    
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

            return true;
        } catch(PDOException $e) {
            error_log("Error: " . $e->getMessage());
            throw new Exception("Ошибка при редактировании пользователя");
        }
    }
}