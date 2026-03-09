<?php

namespace PHPFramework;

class Request
{

    public string $uri;

    public function __construct(string $uri)
    {
        $this->uri = trim(urldecode($uri),'/');

    }


    public function getPath(): string
    {
        return $this->removeQueryString();
    }

    public function getMethod(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD']);
    }

    public function isGet(): bool
    {
        return $this->getMethod() == 'GET';
    }

    public function isPost(): bool
    {
        return $this->getMethod() == 'POST';
    }

    public function isAjax(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }

    public function removeQueryString(): string
    {
        if($this->uri)
        {
            $params = explode('&',$this->uri);
    	    if (false === str_contains($params[0], '='))
	        {
                return trim($params[0],'/');
            }
        }


        return "";

    }

    public function get($name, $default = null): ?string
    {
        return $_GET[$name] ?? $default;
    }

    public function post($name, $default = null): ?string
    {
        return $_POST[$name] ?? $default;
    }

    public function getData(): array
    {
        $data = [];
        $request_data = $this->isGet() ? $_GET : $_POST;
        foreach ($request_data as $name => $value){
            if (is_string($value)){
                $value = trim($value);
            }
            $data[$name] = $value;
        }
        return $data;
    }



}