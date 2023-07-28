<?php


class FilesController {
    private $conn;

    private $usersFolder = '';
    
    public function __construct($db)
    {
        $this->conn = $db;

        if(!isset($_SESSION['user_id'])){
            error_log("Error: user is not authorized");
            throw new Exception("Ошибка, пользователь не авторизован");
        }
    
        $this->usersFolder = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "usersFiles" . DIRECTORY_SEPARATOR;

        if(!is_dir($this->usersFolder)) {
            error_log("Error: user directory not found");
            throw new Exception("Ошибка, не найдена дирректория для пользовательских файлов");
        }
    }

    public function listFile() {

    }
    public function getFile($request) {

    }

    public function addFile($request) {
        if (!isset($request['filePath']) || !isset($request['file'])) {
            error_log("Error: Not all information was provided");
            throw new Exception("Ошибка при получении данных");
        }

        $file           = $request['file'];
        $fileName       = $file['name'];
        $pathParts      = pathinfo($file['name']);
        $fileTmpPath    = $file['tmp_name'];
        $fileSize       = round($file['size'] / (1024 * 1024), 3) . " Мбайт";
        $fileUpdateDate = date("Y-m-d H:i:s");
        $filePath       = $request['filePath'];

        if(!$this->getUniqueName($pathParts, $filePath)) {
            throw new Exception("Недопустимое имя файла");
        }

        $fileName = $this->getUniqueName($pathParts, $filePath);

        if($filePath === '') {
            $filePath = "BASE_ROOT";
        }

        try{
            $searchDirectories = $this->conn->prepare("SELECT * FROM directories WHERE directories_path = :directories_path AND owner_id = :owner_id");
            $searchDirectories->bindParam(":directories_path", $filePath);
            $searchDirectories->bindParam(":owner_id", $_SESSION['user_id']);

            $searchDirectories->execute();
            $directoryInf = $searchDirectories->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Error: " . $e->getMessage());
            throw new Exception("Ошибка подключения к базе данных");
        }

        $directoryID = null;

        if (!$directoryInf) {
            throw new Exception("Не найдена дирректория");
        }

        $directoryID = $directoryInf[0]['id'];

        try{
            $checkReplacement = $this->conn->prepare("SELECT * FROM files WHERE file_name = :file_name AND file_path = :file_path AND owner_id = :owner_id");
            $checkReplacement->bindParam(":file_name", $fileName);
            $checkReplacement->bindParam(":file_path", $directoryID);
            $checkReplacement->bindParam(":owner_id", $_SESSION['user_id']);
            $checkReplacement->execute();

            $filesCount = $checkReplacement->fetchColumn();
        } catch(PDOException $e) {
            error_log("Error: " . $e->getMessage());
            print_r('da');
            throw new Exception("Ошибка подключения к базе данных");
        }

        if($filesCount > 0) {
            throw new Exception("Файл с таким именем уже существует"); 
        }
        
        try{
            $addFile = $this->conn->prepare("INSERT INTO files (file_name, file_path, file_size, file_update_date, owner_id) VALUES (:file_name, :file_path, :file_size, :file_update_date, :owner_id)");
            $addFile->bindParam(":file_name", $fileName);
            $addFile->bindParam(":file_path", $directoryID);
            $addFile->bindParam(":file_size", $fileSize);
            $addFile->bindParam(":file_update_date", $fileUpdateDate);
            $addFile->bindParam(":owner_id", $_SESSION['user_id']);
            $addFile->execute();
        } catch(PDOException $e) {
            error_log("Error: " . $e->getMessage());
            print_r('da');
            throw new Exception("Ошибка подключения к базе данных");
        }

        move_uploaded_file($fileTmpPath, $this->usersFolder . $fileName);
    }

    public function renameFile($request) {
        // TODO:
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

    public function getUniqueName($pathParts, $filePath) {
        if($filePath === '') {
            $filePath = 'BASE_ROOT';
        }
        if (!preg_match('/^[a-zA-Zа-яА-Я0-9-_\.]+$/', $pathParts['filename'])) {
            return false;
        }

        $uniqueName = $pathParts['filename']. "+{" . $_SESSION["user_email"] . "}+{" . $_SESSION['user_id'] . "}+{" . $filePath ."}" . "." . $pathParts['extension'];
        return $uniqueName;
    }
}

