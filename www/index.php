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


class Router {
    private $db;
    private $urlList;

    public function __construct($db, $urlList) {
        $this->db = $db;
        $this->urlList = $urlList;
    }

    public function route() {
        $requestedUrl  = $_SERVER['REQUEST_URI'];
        $httpMethod    = $_SERVER['REQUEST_METHOD'];
        $matchingRoute = null;
        $extractedText = null;
        $routeParams     = [];

        foreach ($this->urlList as $url => $methods) {
            if (preg_match('#^' . preg_replace("/\{(.+?)\}/U", '(\w+)', $url) . '$#', $requestedUrl, $matches)) {
                $matchingRoute = $url;

                preg_match('/{([^}]+)}/', $url, $extractedMatches);

                if (count($extractedMatches) > 1) {
                    $extractedText = $extractedMatches[1];
                }

                if(isset(array_slice($matches, 1)[0])) {
                    $routeParams = [$extractedText => array_slice($matches, 1)[0]];
                }

                break;
            }
        }

        if ($matchingRoute) {
            if (!array_key_exists($httpMethod, $this->urlList[$matchingRoute])) {
                http_response_code(404);
                return;
            }

            $controllerMethod = $this->urlList[$matchingRoute][$httpMethod];

            list($controllerClass, $methodName) = explode("@", $controllerMethod);

            spl_autoload_register(function ($class) {
                include (__DIR__ . DIRECTORY_SEPARATOR . "controllers" . DIRECTORY_SEPARATOR . $class . '.php');
            });

            $controller = new $controllerClass($this->db);

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
}

$router = new Router($db, $urlList);

$router->route();