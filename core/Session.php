<?php

namespace PHPFramework;

class Session
{
    public function __construct()
    {
        session_start();
    }

    public function setFlash($key,$value)
    {
        $_SESSION['flash'][$key] = $value;
    }

    public function getFlash($key)
    {
        if (isset($_SESSION['flash'][$key]))
        {
           $value = $_SESSION['flash'][$key];
           unset($_SESSION['flash'][$key]);
        }
        return $value ?? null;

    }

    public function set($key, $value)
    {
        $keys = explode('.', $key);
        $session_data =& $_SESSION;
        foreach ($keys as $k) {
            if (!isset($session_data[$k]) || !is_array($session_data[$k])) {
                $session_data[$k] = [];
            }
            $session_data =& $session_data[$k];
        }
        $session_data = $value;
    }

    public function get($key, $default = null)
    {
        $keys = explode('.', $key);
        $session_data = $_SESSION;
        foreach ($keys as $k) {
            if (isset($session_data[$k]))
            {
                $session_data = $session_data[$k];
            }else{
                return $default;
            }
        }

        return $session_data;
    }

    public function has($key):bool
    {
        $keys = explode('.', $key);
        $session_data = $_SESSION;
        foreach ($keys as $k) {
            if (isset($session_data[$k]))
            {
                $session_data = $session_data[$k];
            }else{
                return false;
            }
        }

        return true;
    }

    public function forget($key)
    {
        $keys = explode('.', $key);
        $last_key = array_pop($keys);
        $session_data =& $_SESSION;
        foreach ($keys as $k) {
            if (!isset($session_data[$k]) || !is_array($session_data[$k])) {
                return;
            }
            $session_data =& $session_data[$k];
        }
        unset($session_data[$last_key]);
    }

    public function delete($key)
    {
        $this->forget($key);
    }

}