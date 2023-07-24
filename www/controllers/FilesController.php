<?php


class FilesController {
    private $conn;

    private $userFolder = null;
    
    public function __construct($db)
    {
        $this->conn = $db;

        if(isset($_SESSION['id'])) {
            $this->userFolder = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "usersFiles" . DIRECTORY_SEPARATOR . "filesFor_" . $_SESSION['id'];
        }
    }

    public function listFile() {
        if($this->userFolder === null) {
            error_log("Error: " . "Not all data was specified when trying to register a user");
            throw new Exception("Ошибка при получении данных");
        }
        if (!is_dir($this->userFolder)) {
            throw new Exception("Ошибка, не найдена директория");
        }
        $files = scandir($this->userFolder);
        $files = array_diff($files, array('.', '..'));

        echo json_encode($files);
    }
    public function getFile($request) {
        
    }
    public function addFile($request) {
        if($this->userFolder === null) {
            error_log("Error: " . "Not all data was specified when trying to register a user");
            throw new Exception("Ошибка при получении данных");
        }
        if (!isset($request['filePath']) || !isset($request['file'])) {
            error_log("Error: " . "Not all data was specified when trying to register a user");
            throw new Exception("Ошибка при получении данных");
        }
        if (!is_dir($request['filePath'])) {
            throw new Exception("Ошибка, не найдена директория");
        }

        $file     = $request['file'];
        $fileName = $file['name'];
        $fileTmpPath = $file['tmp_name'];
        $filePath = $this->userFolder . DIRECTORY_SEPARATOR . $request['filePath'];

        if (!move_uploaded_file($fileTmpPath, $filePath . $fileName)) {
            throw new Exception("Ошибка, при загрузке файла");
        }

        try{
            $stmt = $this->conn->prepare("INSERT INTO files (file_name, file_path, owner_id) VALUES (:file_name, :file_path, :owner_id)");
            $stmt->bindParam(':file_name', $fileName, PDO::PARAM_STR);
            $stmt->bindParam(':file_path', $request['filePath'], PDO::PARAM_STR);
            $stmt->bindParam(':owner_id', $_SESSION['id'], PDO::PARAM_STR);
            $stmt->execute();
        } catch(PDOException $e) {
            error_log("Error: " . $e->getMessage(), 0);
            throw new Exception("Ошибка подключения к базе данных");
        }
    }

    public function renameFile() {
        
    }
    public function removeFile() {
        
    }
    public function addDirectories() {
        
    }
    public function renameDirectories() {
        
    }
    public function getDirectories() {
        
    }
    public function deleteDirectories() {
        
    }
    public function shareList() {
        
    }
    public function getShare() {
        
    }
    public function deleteShare() {
        
    }
}

