<?php


class FilesController {
    private $conn;

    private $userFolder = '';
    
    public function __construct($db)
    {
        $this->conn = $db;

        if(isset($_SESSION['id'])) {
            $this->userFolder = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "usersFiles" . DIRECTORY_SEPARATOR . "filesFor_" . $_SESSION['id'];
        } else {
            error_log("Error: user is not authorized");
            throw new Exception("Ошибка, пользователь не авторизован");
        }
        if(!is_dir($this->userFolder)) {
            error_log("Error: user directory not found");
            throw new Exception("Ошибка, не найдена дирректория пользователя");
        }
    }

    public function listFile() {
        if($this->userFolder === null) {
            error_log("Error: Not all information was provided");
            throw new Exception("Ошибка при получении данных");
        }
        if (!is_dir($this->userFolder)) {
            throw new Exception("Ошибка, не найдена директория");
        }
        $files  = scandir($this->userFolder);
        $files  = array_diff($files, array('.', '..'));
        $result = array();

        foreach ($files as $file) {
            if (is_dir($this->userFolder. DIRECTORY_SEPARATOR . $file)) {
                $result[] = array("name" => $file, "type" => "directory");
            } else {
                $result[] = array("name" => $file, "type" => "file");
            }
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($result);
    }
    public function getFile($request) {
        if (!isset($request['id'])) {
            error_log("Error: Not all information was provided");
            throw new Exception("Ошибка при получении данных");
        }

        $id = $request['id'];
        try{
            $stmt = $this->conn->prepare("SELECT * FROM files WHERE id = :id");
            $stmt->bindParam(":id", $id);
            $stmt->execute();

            $file = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if($file === []) {
                throw new Exception("Файл не найден");
            }

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($file);
        } catch(PDOException $e) {
            error_log("Error: " . $e->getMessage(), 0);
            throw new Exception("Ошибка подключения к базе данных");
        }
    }

    public function addFile($request) {
        if (!isset($request['filePath']) || !isset($request['file'])) {
            error_log("Error: Not all information was provided");
            throw new Exception("Ошибка при получении данных");
        }
        if (!is_dir($request['filePath'])) {
            throw new Exception("Ошибка, не найдена директория");
        }

        $file           = $request['file'];
        $fileName       = $file['name'];
        $fileTmpPath    = $file['tmp_name'];
        $fileSize       = round($file['size'] / (1024 * 1024), 3) . " Мбайт";
        $fileUpdateDate = date("Y-m-d H:i:s");
        $filePath       = $request['filePath'];

        $fullFilePath = $this->userFolder . DIRECTORY_SEPARATOR . $filePath;
        if (!move_uploaded_file($fileTmpPath, $fullFilePath . $fileName)) {
            throw new Exception("Ошибка, при загрузке файла");
        }

        try{
            $seachFile =  $this->conn->prepare("SELECT * FROM files WHERE file_name = :file_name");
            $seachFile->bindParam(':file_name', $fileName, PDO::PARAM_STR);
            $seachFile->execute();

            if ($seachFile->rowCount() > 0) {
                throw new Exception("Файл с таким именем уже существует");
            }

            $addFile = $this->conn->prepare("INSERT INTO files (file_name, file_path, file_size, file_update_date, owner_id) VALUES (:file_name, :file_path, :file_size, :file_update_date, :owner_id)");
            $addFile->bindParam(':file_name', $fileName, PDO::PARAM_STR);
            $addFile->bindParam(':file_path', $filePath, PDO::PARAM_STR);
            $addFile->bindParam(':file_size', $fileSize, PDO::PARAM_STR);
            $addFile->bindParam(':file_update_date', $fileUpdateDate, PDO::PARAM_STR);
            $addFile->bindParam(':owner_id', $_SESSION['id'], PDO::PARAM_STR);
            $addFile->execute();
        } catch(PDOException $e) {
            error_log("Error: " . $e->getMessage(), 0);
            throw new Exception("Ошибка подключения к базе данных");
        }
    }

    public function renameFile($request) {
        // TODO:
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

