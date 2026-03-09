<?php

namespace PHPFramework;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class View
{

    public string $layout;
    public string $content = '';

    public Environment $twig;

    public function __construct($layout)
    {
        if (!empty(THEME)){
            $path = VIEWS . "/themes/" . THEME; 
        }else{
            $path = VIEWS;
        }
        $loader = new FilesystemLoader($path);
        $this->twig = new Environment($loader, [
            'cache' => CACHE,
        ]);
        $this->layout = $layout;

    }

    public function render($view, $data = [], $layout = '', $twig_template_enable = USE_TWIG_DEFAULT ):string
    {
   
        extract($data);

        if (!empty(THEME)){
            $path = VIEWS . "/themes/" . THEME; 
        }else{
            $path = VIEWS;
        }
        

        if (!$twig_template_enable) {
            $view_file = $path . "/{$view}.php";

            if (is_file($view_file))
            {
                ob_start();
                require $view_file;
                $this->content = ob_get_clean();
            }else{
                abort("View file not found {$view_file}", 500);
            }

            if (false === $layout) {
                return $this->content;
            }

            $layout_file_name = $layout ?: $this->layout;
            $layout_file = VIEWS . "/layouts/{$layout_file_name}.php";

            if (is_file($layout_file)) {
                ob_start();
                require_once $layout_file;
                return ob_get_clean();
            } else {
                abort("Not found layout {$layout_file}", 500);
            }
        }else{
            $view_twig_file = "{$view}.twig";
            $view_twig_file_path = $path . "/{$view}.twig";
            if (is_file($view_twig_file_path))
            {
                return $this->twig->render($view_twig_file, $data);

            }else{
                abort("View Twig Template file not found {$view_twig_file_path}", 500);
            }

        }
        return '';

    }

    public function renderPartial($view, $data = []):string
    {
        extract($data);
        
        if (!empty(THEME)){
            $path = VIEWS . "/themes/" . THEME; 
        }else{
            $path = VIEWS;
        }
        $view_file = $path . "/{$view}.php";
        if (is_file($view_file)) {
            ob_start();
            require $view_file;
            return ob_get_clean();
        }else{
            echo " File {$view_file} not found";
        }
        return '';
    }

}