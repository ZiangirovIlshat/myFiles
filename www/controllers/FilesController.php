<?php


class FilesController {
    private $conn;

    private $usersFolder = '';
    
    public function __construct($db)
    {
        $this->conn = $db;

        if(!isset($_SESSION['user_id'])){
            http_response_code(401);
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
        try{
            $searchFile = $this->conn->prepare("SELECT files.id, files.owner_id, files.file_name, files.file_path, files.file_size, files.file_update_date
            FROM files
            LEFT JOIN access ON access.file_id = files.id
            WHERE files.owner_id = :user_id OR access.user_id = :user_id");
            $searchFile->bindParam(":user_id", $_SESSION['user_id']);
        
            $searchFile->execute();
            $files = $searchFile->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Error: " . $e->getMessage());
            throw new Exception("Ошибка при получении информации о файлах");
        }


        foreach($files as $file) {
            $pattern = "/\{(.*?)\}/";
            preg_match_all($pattern, $file['file_name'], $matches);
            $fileInfo = [];
            $result   = $matches[1];

            $fileInfo['id']          = $file['id'];
            $fileInfo['name']        = $result[0];
            $fileInfo['extension']   = pathinfo($file['file_name'])['extension'];
            $fileInfo['owner']       = $result[1];
            $fileInfo['file_path']   = $result[3];
            $fileInfo['file_size']   = $file['file_size'];
            $fileInfo['update_date'] = $file['file_update_date'];

            header('Content-Type: application/json; charset=utf-8');
            echo(json_encode($fileInfo, JSON_UNESCAPED_UNICODE));
        }
    }

    public function getFile($request) {
        if (!isset($request['id'])) {
            error_log("Error: Not all information was provided");
            throw new Exception("Ошибка при получении данных");
        }

        $id = $request['id'];

        try {
            $searchFile = $this->conn->prepare("SELECT files.id, files.owner_id, files.file_name, files.file_path, files.file_size, files.file_update_date
            FROM files
            LEFT JOIN access ON access.file_id = files.id
            WHERE (files.owner_id = :user_id OR access.user_id = :user_id) AND files.id = :file_id");
        
            $searchFile->bindParam(":user_id", $_SESSION['user_id']);
            $searchFile->bindParam(":file_id", $id);
        
            $searchFile->execute();
            $file = $searchFile->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Error: " . $e->getMessage());
            throw new Exception("Ошибка при получении информации о файле");
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
        if (!isset($request['directoryID']) || !isset($request['file'])) {
            error_log("Error: Not all information was provided");
            throw new Exception("Ошибка при получении данных");
        }

        $file           = $request['file'];
        $fileName       = $file['name'];
        $pathParts      = pathinfo($file['name']);
        $fileTmpPath    = $file['tmp_name'];
        $fileSize       = round($file['size'] / (1024 * 1024), 3) . " Мбайт";
        $fileUpdateDate = date("Y-m-d H:i:s");
        $directoryID    = $request['directoryID'];

        if(!$this->getUniqueName($pathParts, $directoryID)) {
            throw new Exception("Недопустимое имя файла");
        }

        $fileName = $this->getUniqueName($pathParts, $directoryID);

        try{
            $searchDirectories = $this->conn->prepare("SELECT * FROM directories WHERE id = :id AND owner_id = :owner_id");
            $searchDirectories->bindParam(":id", $directoryID);
            $searchDirectories->bindParam(":owner_id", $_SESSION['user_id']);

            $searchDirectories->execute();
            $directoryInf = $searchDirectories->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Error: " . $e->getMessage());
            throw new Exception("Ошибка при поиске директории");
        }

        if (!$directoryInf) {
            throw new Exception("Не найдена дирректория");
        }


        try{
            $checkReplacement = $this->conn->prepare("SELECT * FROM files WHERE file_name = :file_name AND file_path = :file_path AND owner_id = :owner_id");
            $checkReplacement->bindParam(":file_name", $fileName);
            $checkReplacement->bindParam(":file_path", $directoryID);
            $checkReplacement->bindParam(":owner_id", $_SESSION['user_id']);
            $checkReplacement->execute();

            $filesCount = $checkReplacement->fetchColumn();
        } catch(PDOException $e) {
            error_log("Error: " . $e->getMessage());
            throw new Exception("Ошибка при проверки уникальности имени файла");
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
            throw new Exception("Ошибка при добавлении информации о файле в БД");
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

        try{
            $getFile = $this->conn->prepare("SELECT * FROM files WHERE id = :id AND owner_id = :ownewID");
            $getFile->bindParam(":id", $id);
            $getFile->bindParam(":ownewID", $_SESSION['user_id']);

            $getFile->execute();
        } catch(PDOException $e) {
            error_log("Error: " . $e->getMessage());
            throw new Exception("Ошибка при поиске информации о файле");
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


        if ($handle = opendir($this->usersFolder)) {
            while (false !== ($file = readdir($handle))) {
                if ($file == $oldName && is_file($this->usersFolder . $file)) {
                    rename($this->usersFolder . $file, $this->usersFolder . $newFullName);
                    echo "Файл переименован успешно!";
                    break;
                }
            }
            closedir($handle);
        }
        
        try{
            $renameFile = $this->conn->prepare("UPDATE files SET file_name = :newFullName WHERE id = :id");
            $renameFile->bindParam(":id", $id);
            $renameFile->bindParam(":newFullName", $newFullName);

            $renameFile->execute();
        } catch(PDOException $e) {
            error_log("Error: " . $e->getMessage());
            throw new Exception("Ошибка при переименовании файла");
        }
    } 

    public function removeFile($request) {
        if (!isset($request['id'])) {
            error_log("Error: Not all information was provided");
            throw new Exception("Ошибка при получении данных");
        }
    
        $id = $request['id'];
    
        try {
            $searchFile = $this->conn->prepare("SELECT * FROM files WHERE id = :id AND owner_id = :owner_id");
            $searchFile->bindParam(":id", $id);
            $searchFile->bindParam(":owner_id", $_SESSION['user_id']);
            $searchFile->execute();
    
        } catch (PDOException $e) {
            error_log("Error: " . $e->getMessage());
            throw new Exception("Ошибка при получении информации о файле");
        }
    
        $fileInf = $searchFile->fetch(PDO::FETCH_ASSOC);
    
        if (!$fileInf) {
            throw new Exception("Файл не найден");
        }

        $fileName = $fileInf['file_name'];

        $search_result = glob($this->usersFolder . $fileName);

        if(!empty($search_result)) {
            unlink($search_result[0]);
        }
    
        try {
            $deleteFile = $this->conn->prepare("DELETE FROM files WHERE id = :id AND owner_id = :owner_id");
            $deleteFile->bindParam(":id", $id);
            $deleteFile->bindParam(":owner_id", $_SESSION['user_id']);
            $deleteFile->execute();
            return "Файл удален";
        } catch (PDOException $e) {
            error_log("Error: " . $e->getMessage());
            throw new Exception("Ошибка при удалении файла");
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
            throw new Exception("Ошибка при получении данных о родительской директории");
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
            throw new Exception("Ошибка при получении данных о наличии папки с таким именем в данной директории");
        }

        print_r($_SESSION['user_id']);

        try{
            $addFile = $this->conn->prepare("INSERT INTO directories (directory_path, owner_id) VALUES (:directory_path, :owner_id)");
            $addFile->bindParam(":directory_path", $directoryPath);
            $addFile->bindParam(":owner_id", $_SESSION['user_id']);

            $addFile->execute();
        } catch(PDOException $e) {
            error_log("Error: " . $e->getMessage());
            throw new Exception("Ошибка при добавлении директории");
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
            throw new Exception("Ошибка при получении данных о директории");
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
            throw new Exception("Ошибка при переименовании директории");
        }
    }

    public function getDirectories($request) {
        if (!isset($request['id'])) {
            error_log("Error: Not all information was provided");
            throw new Exception("Ошибка при получении данных");
        }

        $id = $request['id'];

        try{
            $searchFile = $this->conn->prepare("SELECT * FROM files WHERE owner_id = :owner_id AND file_path = :file_path");
            $searchFile->bindParam(":file_path", $id);
            $searchFile->bindParam(":owner_id", $_SESSION['user_id']);

            $searchFile->execute();
            $files = $searchFile->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Error: " . $e->getMessage());
            throw new Exception("Ошибка при получении данных о директории");
        }

        foreach($files as $file) {
            $pattern = "/\{(.*?)\}/";
            preg_match_all($pattern, $file['file_name'], $matches);
            $fileInfo = [];
            $result   = $matches[1];

            $fileInfo['id']          = $file['id'];
            $fileInfo['name']        = $result[0];
            $fileInfo['extension']   = pathinfo($file['file_name'])['extension'];
            $fileInfo['owner']       = $result[1];
            $fileInfo['file_path']   = $result[3];
            $fileInfo['file_size']   = $file['file_size'];
            $fileInfo['update_date'] = $file['file_update_date'];

            header('Content-Type: application/json; charset=utf-8');
            echo(json_encode($fileInfo, JSON_UNESCAPED_UNICODE));
        }
    }

    public function deleteDirectories($request) {
        if (!isset($request['id'])) {
            error_log("Error: Not all information was provided");
            throw new Exception("Ошибка при получении данных");
        }

        $id = $request['id'];

        try{
            $checkDirectory = $this->conn->prepare("SELECT * FROM directories WHERE id = :id");
            $checkDirectory->bindParam(":id", $id);
            $checkDirectory->execute();

            $checkDirectoryResult = $checkDirectory->fetchAll(PDO::FETCH_ASSOC);
            $directoriesCount     = $checkDirectory->fetchColumn();
        } catch(PDOException $e) {
            error_log("Error: " . $e->getMessage());
            throw new Exception("Ошибка при поиске директории");
        }

        if($directoriesCount === 0) {
            throw new Exception("Не найденна папка");
        }

        if($checkDirectoryResult[0]['directory_path'] === 'BASE_ROOT') {
            throw new Exception("Невозможно удалить данную папку");
        }

        try{
            $deleteDirectory =  $this->conn->prepare("DELETE FROM directories WHERE id = :id");
            $deleteDirectory->bindParam(':id', $id);

            $deleteDirectory->execute();
        } catch(PDOException $e) {
            error_log("Error: " . $e->getMessage());
            throw new Exception("Ошибка при удалении директории");
        }
    }



    public function shareList($request) {
        if (!isset($request['id'])) {
            error_log("Error: Not all information was provided");
            throw new Exception("Ошибка при получении данных");
        }

        $fileID = $request['id'];

        try{
            $searchUser = $this->conn->prepare("SELECT user_id FROM access WHERE file_id = :file_id");
            $searchUser->bindParam(":file_id", $fileID);
            $searchUser->execute();

            $searchUserResult = $searchUser->fetchAll(PDO::FETCH_ASSOC);
            $usersCount       = $searchUser->fetchColumn();
        } catch(PDOException $e) {
            error_log("Error: " . $e->getMessage());
            throw new Exception("Ошибка при поиске пользователей имеющих доступ к данному файлу");
        }

        if($usersCount === 0) {
            throw new Exception("Нет пользователей имеющих доступ к данному файлу");
        }

        foreach($searchUserResult[0] as $i) {
            try{
                $getUser = $this->conn->prepare("SELECT email FROM users WHERE id = :id");
                $getUser->bindParam(":id", $i);
                $getUser->execute();
    
                $userInf = $getUser->fetchAll(PDO::FETCH_ASSOC);

                header('Content-Type: application/json; charset=utf-8');
                echo(json_encode($userInf[0], JSON_UNESCAPED_UNICODE));
            } catch(PDOException $e) {
                error_log("Error: " . $e->getMessage());
                throw new Exception("Ошибка при попытке получить информацию о пользователях имеющих доступ к этому файлу");
            }
        }
    }
    
    public function getShare($request) {
        if (!isset($request['id']) || !isset($request['user_id'])) {
            error_log("Error: Not all information was provided");
            throw new Exception("Ошибка при получении данных");
        }

        $fileID = $request['id'];
        $userID = $request['user_id'];

        try{
            $searchUser = $this->conn->prepare("SELECT * FROM users WHERE id = :id");
            $searchUser->bindParam(":id", $userID);
            $searchUser->execute();

            $usersCount       = $searchUser->fetchColumn();
        } catch(PDOException $e) {
            error_log("Error: " . $e->getMessage());
            throw new Exception("Ошибка при поиске информации о пользователе");
        }

        if($usersCount === 0) {
            throw new Exception("Не найден пользователь");
        }

        try{
            $searchFile = $this->conn->prepare("SELECT * FROM files WHERE id = :id AND owner_id = :owner_id");
            $searchFile->bindParam(":id", $fileID);
            $searchFile->bindParam(":owner_id", $_SESSION['user_id']);
            $searchFile->execute();

            $filesCount       = $searchFile->fetchColumn();
        } catch(PDOException $e) {
            error_log("Error: " . $e->getMessage());
            throw new Exception("Ошибка при поиске информации о файле");
        }

        if($filesCount === 0) {
            throw new Exception("Не найден файл");
        }

        try{
            $searchShare = $this->conn->prepare("SELECT * FROM access WHERE file_id = :file_id AND user_id = :user_id");
            $searchShare->bindParam(":file_id", $fileID);
            $searchShare->bindParam(":user_id", $userID);
            $searchShare->execute();

            $shareCount = $searchShare->fetchColumn();
        } catch(PDOException $e) {
            error_log("Error: " . $e->getMessage());
            throw new Exception("Ошибка при поиске информации о доступе к файлу");
        }

        if($shareCount > 0) {
            throw new Exception("У пользователя уже есть доступ к этому файлу");
        }

        try{
            $searchShare = $this->conn->prepare("INSERT INTO access (file_id, user_id) VALUES (:file_id, :user_id)");
            $searchShare->bindParam(":file_id", $fileID);
            $searchShare->bindParam(":user_id", $userID);
            $searchShare->execute();
        } catch(PDOException $e) {
            error_log("Error: " . $e->getMessage());
            throw new Exception("Ошибка при добавлении доступа к файлу");
        }
    }

    public function deleteShare($request) {
        if (!isset($request['id']) || !isset($request['user_id'])) {
            error_log("Error: Not all information was provided");
            throw new Exception("Ошибка при получении данных");
        }

        $fileID = $request['id'];
        $userID = $request['user_id'];

        try{
            $searchShare = $this->conn->prepare("SELECT * FROM access WHERE user_id = :user_id AND file_id = :file_id");
            $searchShare->bindParam(":user_id", $userID);
            $searchShare->bindParam(":file_id", $fileID);
            $searchShare->execute();

            $shareCount = $searchShare->fetchColumn();
        } catch(PDOException $e) {
            error_log("Error: " . $e->getMessage());
            throw new Exception("Ошибка при поиске информации о доступе к файлу");
        }

        if($shareCount === 0) {
            throw new Exception("Не найдена информация о доступе");
        }

        try{
            $deleteShare =  $this->conn->prepare("DELETE FROM directories WHERE user_id = :user_id AND file_id = :file_id");
            $deleteShare->bindParam(":user_id", $userID);
            $deleteShare->bindParam(":file_id", $fileID);
            $deleteShare->execute();
        } catch(PDOException $e) {
            error_log("Error: " . $e->getMessage());
            throw new Exception("Ошибка при удалении доступа к данному файлу");
        }
    }



    public function getUniqueName($pathParts, $directoryID) {
        if (!preg_match('/^[a-zA-Zа-яА-Я0-9_-]+$/', $pathParts['filename'])) {
            return false;
        }

        $uniqueName = "{" . $pathParts['filename'] . "}+{" . $_SESSION["user_email"] . "}+{" . $_SESSION['user_id'] . "}+{" . $directoryID ."}" . "." . $pathParts['extension'];
        return $uniqueName;
    }

    public function checkingFolderName($name) {
        if(strlen($name) < 4) {
            throw new Exception("Название папки должно содержать хотя бы 4 символа");
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

