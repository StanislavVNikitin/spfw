<?php

namespace PHPFramework;

class Router
{

    public Request $request;
    public Response $response;

    protected array $routes = [];

    public array $route_params = [];

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * @return array
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    public function add($path, $callback, $method): self
    {
        $path = trim($path, '/');
        if (is_array($method)) {
            $method = array_map('strtoupper', $method);
        }else{
            $method = [strtoupper($method)];
        }
        $this->routes[] = [
            'path' => "/$path",
            'callback' => $callback,
            'middleware' => null,
            'method' => $method,
            'needCsrfToken' => true,
        ];
        /*foreach ($method as $item_method) {
            $this->routes[$item_method]["/{$path}"] = [
                'callback' => $callback,
                'middleware' => null
            ];
        }*/

        return $this;
    }

    public function get($path, $callback):self
    {
        return $this->add($path, $callback, 'GET');
    }

    public function post($path, $callback):self
    {
        return $this->add($path, $callback, 'POST');
    }

    public function put($path, $callback):self
    {
        return $this->add($path, $callback, 'PUT');
    }

    public function delete($path, $callback):self
    {
        return $this->add($path, $callback, 'DELETE');
    }




    public function dispatch(): mixed
    {
        $path = $this->request->getPath();
        $method = $this->request->getMethod();
        $callback = $this->matchRoute($method,$path);

        if (!$callback) {
            abort();
        }
        if (is_array($callback['callback'])) {
            $callback['callback'][0] = new $callback['callback'][0];
            app()->layout = $callback['callback'][0]->layout;
        }
        return call_user_func($callback['callback']);

    }

    protected function matchRoute(string $method, string $path)
    {
        $allowed_methods = [];
        foreach ($this->routes as $route) {
            if ((preg_match("#^{$route['path']}$#", "/{$path}", $matches))
                //&& (in_array($this->request->getMethod(), $route['method']))
            )
            {
                if (!in_array($this->request->getMethod(), $route['method'])){
                    $allowed_methods = array_merge($allowed_methods,$route['method']);
                    continue;
                }

                if($route['middleware']) {
                    $middleware = MIDDLEWARE[$route['middleware']] ?? false;
                    if($middleware){
                        (new $middleware)->handle();
                    }
                }
                foreach ($matches as $k => $v)
                {
                    if(is_string($k))
                    {
                        $this->route_params[$k] = $v;
                    }
                }

                if(request()->isPost())
                {
                    if($route['needCsrfToken'] && !$this->checkCSRFToken())
                    {
                        if (request()->isAjax()) {
                            echo json_encode([
                                'status' => 'error',
                                'data' => 'Security error',
                            ]);
                            die;

                        }else {
                            session()->setFlash('error', 'Security error');
                            response()->redirect();
                            //abort('Page expired',419);
                        }
                    }

                }

                return $route;

            }
        }
        if($allowed_methods){
            header("Allow: " . implode(', ', $allowed_methods));
            if ($_SERVER['HTTP_ACCEPT'] == 'application/json')
            {
                 response()->json(['status' => 'error', 'answer' => "Method not allowed"], 405);
            }
            abort('Method Not Allowed', 405);

        }

        return false;
    }

    public function only($middleware)
    {
        $this->routes[array_key_last($this->routes)]['middleware'] = $middleware;
        return $this;

    }

    public function withoutCsrfToken()
    {
        $this->routes[array_key_last($this->routes)]['needCsrfToken'] = false;
        return $this;
    }


    public function checkCsrfToken(): bool
    {
        return request()->post('csrf_token') && (request()->post('csrf_token') == session()->get('csrf_token'));
    }




}