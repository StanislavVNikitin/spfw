<?php

namespace PHPFramework;

class Response
{


    public function setResponseCode(int $code)
    {
        http_response_code($code);
    }

    public function redirect(string $url = '')
    {
        if ($url)
        {
            $redirect = $url;
        } else
        {
            $redirect = $_SERVER['HTTP_REFERER'] ?? base_url();
        }
        header('Location: ' . $redirect);
        die();
    }

    public function json($data, $code = 200)
    {
        http_response_code($code);
        header('Content-Type: application/json');
        exit(json_encode($data));
    }

}