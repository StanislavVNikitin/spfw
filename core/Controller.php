<?php

namespace PHPFramework;


abstract class Controller
{
    public string $layout = LAYOUT;

    public function render($view, $data = [], $layout = ''):string
    {
        if (false !== $layout) {
            $layout = $layout ?: app()->layout;
        }

        return app()->view->render($view, $data, $layout);
    }
}