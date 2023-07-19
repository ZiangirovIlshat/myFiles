<?php


class AdminController {
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;

        if(!isset($_SESSION['id']) || $_SESSION['role'] != 1) {
            header('Location: /');
            return;
        }
    }

    public function list(){
        try {
            $stmt = $this->conn->prepare("SELECT id, email, password, role FROM users");
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($users);
        } catch(PDOException $e) {
            error_log("Error retrieving the list of users:" . $e->getMessage(), 0);
            return array();
        }
    }
    public function getUser($request){
        $id = $request['id'];
        try {
            $stmt = $this->conn->prepare("SELECT id, email, role, password FROM users WHERE id = :id");
            $stmt->bindParam(":id", $id);
            $stmt->execute();

            $user = $stmt->fetchAll(PDO::FETCH_ASSOC);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($user);
        } catch(PDOException $e) {
            print_r($e->getMessage());
            error_log("Error updating user data: " . $e->getMessage());
            return false;
        }
    }
    public function deleteUser($request){
        $id = $request['id'];
        try {
            $stmt = $this->conn->prepare("DELETE FROM users WHERE id = :id");
            $stmt->bindParam(":id", $id);
            $stmt->execute();

        } catch(PDOException $e) {
            error_log("Error updating user data: " . $e->getMessage());
            return false;
        }
    }
    public function update($request){
        $email    = $request['email'];
        $password = password_hash($request['password'], PASSWORD_DEFAULT);
        $id       = $request['id'];

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
            print_r($e->getMessage());
            error_log("Error updating user data: " . $e->getMessage());
            return false;
        }
    }
}