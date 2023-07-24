<?php 

session_start();

// подключение к бд
require_once (__DIR__ . DIRECTORY_SEPARATOR . "conf" . DIRECTORY_SEPARATOR . "database.php");

// Файл создания исходных данных в бд 
require_once (__DIR__ . DIRECTORY_SEPARATOR . "conf" . DIRECTORY_SEPARATOR . "core.php");

// файл содержащий список обрабатываемых адресов
require_once (__DIR__ . DIRECTORY_SEPARATOR . "url.php");


$database = new Database();
$db = $database->getConnection();


class Router {
    private $db;
    private $urlList;
    private $requestedUrl;
    private $httpMethod;
    private $matchingRoute = null;
    private $routeParams = [];

    public function __construct($db, $urlList) {
        $this->db            = $db;
        $this->urlList       = $urlList;
        $this->requestedUrl  = $_SERVER['REQUEST_URI'];
        $this->httpMethod    = $_SERVER['REQUEST_METHOD'];
    }


    public function searchMatches() {
        foreach ($this->urlList as $url => $methods) {
            $regex = '#^' . preg_replace("/{(.+?)}/", '([^/]+)', $url) . '$#';
        
            if (preg_match($regex, $this->requestedUrl, $matches)) {
                $this->matchingRoute = $url;
                preg_match_all('/{([^}]+)}/', $url, $extractedMatches);
                $extractedText = null;

                if (count($extractedMatches[1]) > 0) {
                    $extractedText = $extractedMatches[1];
                    for ($i = 0; $i < count($extractedText); $i++) {
                        if (isset($matches[$i + 1])) {
                            $this->routeParams[$extractedText[$i]] = $matches[$i + 1];
                        }
                    }
                }
                break;
            }
        }
    }

    public function route() {
        $this->searchMatches();

        if (!$this->matchingRoute) {
            http_response_code(404);
            return;
        }

        if (!array_key_exists($this->httpMethod, $this->urlList[$this->matchingRoute])) {
            http_response_code(404);
            return;
        }

        $controllerMethod = $this->urlList[$this->matchingRoute][$this->httpMethod];

        list($controllerClass, $methodName) = explode("@", $controllerMethod);

        spl_autoload_register(function ($class) {
            include (__DIR__ . DIRECTORY_SEPARATOR . "controllers" . DIRECTORY_SEPARATOR . $class . '.php');
        });

        $controller = new $controllerClass($this->db);

        try{
            switch ($this->httpMethod) {
                case "GET":
                    $request = $_GET;
                    unset($_GET);
                    $request += $this->routeParams;
                    $controller->$methodName($request);
                    break;

                case "POST":
                    $request = $_POST;
                    unset($_POST);
                    $request += $this->routeParams;
                    $controller->$methodName($request);
                    break;

                case "PUT":
                    $body = file_get_contents('php://input');
                    parse_str($body, $request);
                    $request += $this->routeParams;
                    $controller->$methodName($request);
                    break;

                case "DELETE":
                    $request = [];
                    $request += $this->routeParams;
                    $controller->$methodName($request);
                    break;

                default:
                    http_response_code(405);
                    break;
            }
        } catch(Exception $e){
            echo $e->getMessage();
        }
    }
}

$router = new Router($db, $urlList);

$router->route();