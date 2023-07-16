<?php 

session_start();

// подключение к бд
require_once (__DIR__ . DIRECTORY_SEPARATOR . "conf" . DIRECTORY_SEPARATOR . "database.php");
// Файл создания исходных данных в бд 
require_once (__DIR__ . DIRECTORY_SEPARATOR . "conf" . DIRECTORY_SEPARATOR . "core.php");

$database = new Database();
$db = $database->getConnection();



// файл содержащий список обрабатываемых адресов
require_once (__DIR__ . DIRECTORY_SEPARATOR . "url.php");


// функция "роутер"
function router($db, $urlList) {
    $requestedUrl = $_SERVER['REQUEST_URI'];
    $httpMethod = $_SERVER['REQUEST_METHOD'];

    $matchingRoute = null;
    $routeParams = [];

    foreach ($urlList as $url => $methods) {
        $pattern = preg_replace('/{(\w+)}/', '(\w+)', $url);
        if (preg_match('#^' . $pattern . '$#', $requestedUrl, $matches)) {
            $matchingRoute = $url;
            $routeParams = array_slice($matches, 1);
            break;
        }
    }

    if ($matchingRoute) {
        if(!array_key_exists($httpMethod, $urlList[$matchingRoute])) {
            http_response_code(404);
            return;
        }

        $controllerMethod = $urlList[$matchingRoute][$httpMethod];

        list($controllerClass, $methodName) = explode("@", $controllerMethod);

        spl_autoload_register(function ($class) {
            include (__DIR__ . DIRECTORY_SEPARATOR . "controllers" . DIRECTORY_SEPARATOR . $class . '.php');
        });

        $controller = new $controllerClass($db);

        switch ($httpMethod) {
            case "GET":
                $request = $_GET;
                unset($_GET);
                $request += $routeParams;
                $controller->$methodName($request);
                break;
            case "POST":
                $request = $_POST;
                unset($_POST);
                $request += $routeParams;
                $controller->$methodName($request);
                break;
            case "PUT":
                $body = file_get_contents('php://input');
                parse_str($body, $request);
                $request += $routeParams;
                $controller->$methodName($request);
                break;
            case "DELETE":
                $request = [];
                $request += $routeParams;
                $controller->$methodName($request);
                break;
            default:
                http_response_code(405);
                break;
        }
    } else {
        http_response_code(404);
    }
}

router($db, $urlList);