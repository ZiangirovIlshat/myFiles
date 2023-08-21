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
        if (!isset($request['id'])) {
            error_log("Error: Not all information was provided");
            throw new Exception("Ошибка при получении данных");
        }

        $id = $request['id'];

        try{
            $searchFile = $this->conn->prepare("SELECT * FROM files WHERE id = :id AND owner_id = :owner_id");
            $searchFile->bindParam(":id", $id);
            $searchFile->bindParam(":owner_id", $_SESSION['user_id']);

            $searchFile->execute();
            $file = $searchFile->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Error: " . $e->getMessage());
            throw new Exception("Ошибка подключения к базе данных");
        }

        if($file === []) {
            throw new Exception("Файл не найден");
        }

        $pattern = "/\{(.*?)\}/";
        preg_match_all($pattern, $file[0]['file_name'], $matches);
        $fileInfo = [];
        $result   = $matches[1];

        $fileInfo['id']          = $file[0]['id'];
        $fileInfo['name']        = $result[0];
        $fileInfo['extension']   = pathinfo($file[0]['file_name'])['extension'];
        $fileInfo['owner']       = $result[1];
        $fileInfo['file_path']   = $result[3];
        $fileInfo['file_size']   = $file[0]['file_size'];
        $fileInfo['update_date'] = $file[0]['file_update_date'];

        header('Content-Type: application/json; charset=utf-8');
        echo(json_encode($fileInfo, JSON_UNESCAPED_UNICODE));
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
            $searchDirectories = $this->conn->prepare("SELECT * FROM directories WHERE directory_path = :directory_path AND owner_id = :owner_id");
            $searchDirectories->bindParam(":directory_path", $filePath);
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
            throw new Exception("Ошибка подключения к базе данных");
        }

        move_uploaded_file($fileTmpPath, $this->usersFolder . $fileName);
    }

    public function renameFile($request) {
        if (!isset($request['id']) || !isset($request['newName'])) {
            error_log("Error: Not all information was provided");
            throw new Exception("Ошибка при получении данных");
        }

        $id      = $request['id'];
        $newName = $request['newName'];
        $oldName = '';
        $newName = $request['newName'];

        try{
            $getFile = $this->conn->prepare("SELECT * FROM files WHERE id = :id AND owner_id = :ownewID");
            $getFile->bindParam(":id", $id);
            $getFile->bindParam(":ownewID", $_SESSION['user_id']);

            $getFile->execute();
        } catch(PDOException $e) {
            error_log("Error: " . $e->getMessage());
            throw new Exception("Ошибка подключения к базе данных");
        }

        $file    = $getFile->fetchAll(PDO::FETCH_ASSOC);

        if($file === []) {
            throw new Exception("Файл не найден");
        }

        if (!preg_match('/^[a-zA-Zа-яА-Я0-9-_\.]+$/', $newName)) {
            throw new Exception("Недопустимое имя файла");
        }

        $oldName     = $file[0]['file_name'];
        $newFullName = '';

        $newFullName = preg_replace_callback('/\{([^{}]*)\}/', function($matches) use ($newName) {
            return "{" . $newName . "}";
        }, $oldName, 1);

        try{
            $renameFile = $this->conn->prepare("UPDATE файлы SET file_name = :newFullName WHERE id = :id");
            $renameFile->bindParam(":id", $id);
            $renameFile->bindParam(":newFullName", $newFullName);

            $renameFile->execute();
        } catch(PDOException $e) {
            error_log("Error: " . $e->getMessage());
            throw new Exception("Ошибка подключения к базе данных");
        }

        
    } 

    public function addDirectory($request) {
        if (!isset($request['parentDirectoryID']) || !isset($request['directoryName'])) {
            error_log("Error: Not all information was provided");
            throw new Exception("Ошибка при получении данных");
        }

        $parentDirectoryID = $request['parentDirectoryID'];
        $directoryName     = $request['directoryName'];
        $path              = '';

        try{
            $checkDirectory = $this->conn->prepare("SELECT * FROM directories WHERE id = :parent_directory_id");
            $checkDirectory->bindParam(":parent_directory_id", $parentDirectoryID);
            $checkDirectory->execute();

            $checkDirectoryResult = $checkDirectory->fetchAll(PDO::FETCH_ASSOC);
            $directoriesCount     = $checkDirectory->fetchColumn();
        } catch(PDOException $e) {
            error_log("Error: " . $e->getMessage());
            throw new Exception("Ошибка подключения к базе данных");
        }

        if($directoriesCount === 0) {
            error_log("Error: parent directory not found");
            throw new Exception("Ошибка. Не найденна родительская дирекетория директория");
        }

        $this->checkingFolderName($directoryName);

        $parentDirectoryName = $checkDirectoryResult[0]['directory_path'];
        $directoryPath       = $parentDirectoryName . '/' . $directoryName;

        try{
            $checkName = $this->conn->prepare("SELECT * FROM directories WHERE directory_path = :directory_path");
            $checkName->bindParam(":directory_path", $directoryPath);
            $checkName->execute();

            $nameCount = $checkName->fetchColumn();
        } catch(PDOException $e) {
            error_log("Error: " . $e->getMessage());
            throw new Exception("Ошибка подключения к базе данных");
        }

        if($nameCount > 0) {
            throw new Exception("В данной директории уже есть папка с таким именем");
        }

        print_r($_SESSION['user_id']);

        try{
            $addFile = $this->conn->prepare("INSERT INTO directories (directory_path, owner_id) VALUES (:directory_path, :owner_id)");
            $addFile->bindParam(":directory_path", $directoryPath);
            $addFile->bindParam(":owner_id", $_SESSION['user_id']);

            $addFile->execute();
        } catch(PDOException $e) {
            error_log("Error: " . $e->getMessage());
            throw new Exception("Ошибка подключения к базе данных");
        }

    }
    public function renameDirectories($request) {
        if (!isset($request['id']) || !isset($request['newName'])) {
            error_log("Error: Not all information was provided");
            throw new Exception("Ошибка при получении данных");
        }

        $id      = $request['id'];
        $newName = $request['newName'];

        $this->checkingFolderName($newName);

        try{
            $checkDirectory = $this->conn->prepare("SELECT * FROM directories WHERE id = :id");
            $checkDirectory->bindParam(":id", $id);
            $checkDirectory->execute();

            $checkDirectoryResult = $checkDirectory->fetchAll(PDO::FETCH_ASSOC);
            $directoriesCount     = $checkDirectory->fetchColumn();
        } catch(PDOException $e) {
            error_log("Error: " . $e->getMessage());
            throw new Exception("Ошибка подключения к базе данных");
        }

        if($directoriesCount === 0) {
            throw new Exception("Не найденна папка");
        }

        if($checkDirectoryResult[0]['directory_path'] === 'BASE_ROOT') {
            throw new Exception("Нельзя переименовывать данную папку");
        }

        $string = $checkDirectoryResult[0]['directory_path'];
        $delimiter = '/';
        $array = explode($delimiter, $string);
        $last = count($array)-1;
        $array[$last] = $newName;
        $newDirectoryPath = implode($delimiter, $array);

        print_r($newDirectoryPath);

        try{
            $renameDirectory = $this->conn->prepare("UPDATE directories SET directory_path = :new_directory_path WHERE id = :id");
            $renameDirectory->bindParam(":new_directory_path", $newDirectoryPath);
            $renameDirectory->bindParam(":id", $id);
            $renameDirectory->execute();

        } catch(PDOException $e) {
            error_log("Error: " . $e->getMessage());
            throw new Exception("Ошибка подключения к базе данных");
        }
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
        if (!preg_match('/^[a-zA-Zа-яА-Я0-9_-]+$/', $pathParts['filename'])) {
            return false;
        }

        $uniqueName = "{" . $pathParts['filename'] . "}+{" . $_SESSION["user_email"] . "}+{" . $_SESSION['user_id'] . "}+{" . $filePath ."}" . "." . $pathParts['extension'];
        return $uniqueName;
    }

    public function checkingFolderName($name) {
        if(strlen($name) < 8) {
            throw new Exception("Название папки должно содержать хотя бы 8 символов");
        }

        if(strlen($name) > 32) {
            throw new Exception("Название папки должно содержать не более 32 символов");
        }

        if($name === 'BASE_ROOT') {
            throw new Exception("Недопустимое имя директории");
        }

        if (!preg_match('/^[a-zA-Zа-яА-Я0-9_-]+$/', $name)) {
            throw new Exception("Недопустимые символы в названии");
        }
    }
}

