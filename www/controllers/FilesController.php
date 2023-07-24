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
    public function addFileFile() {
        
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

