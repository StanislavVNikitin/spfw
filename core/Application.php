<?php

namespace PHPFramework;

use http\Encoding\Stream\Deflate;

class Application
{

    public string $uri;

    public Request $request;

    public Response $response;

    public Router $router;

    public string $layout = LAYOUT;

    public View $view;

    public Database $db;

    public Session $session;

    public Cache $cache;

    public static Application $app;

    protected array $container = [];

    public function __construct()
    {
        self::$app = $this;
        $this->uri = $_SERVER['QUERY_STRING'];
        $this->request = new Request($this->uri);
        $this->response = new Response();
        $this->router = new Router($this->request, $this->response);
        $this->view = new View(LAYOUT);
        $this->db = new Database();
        $this->session = new Session();
        $this->generateCsrfToken();
        $this->cache = new Cache();
    }

    public function run():void
    {
       echo $this->router->dispatch();
    }

    public function get($key, $default = null)
    {
        return $this->container[$key] ?? $default;
    }

    public function set($key, $value):void
    {
        $this->container[$key] = $value;
    }

    public function generateCsrfToken():void
    {
        if(!session()->has('csrf_token')){
            session()->set('csrf_token', md5(uniqid(mt_rand(), true)));
        }
    }


}