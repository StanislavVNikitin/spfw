<?php 
define('ROOT', dirname(__DIR__));

const DEBUG = false;

const ERROR_LOG_FILE = ROOT . '/logs/errors.log';
const WWW = ROOT . '/public';
const UPLOADS = WWW . '/uploads';
const APP_PATH_NAME = 'app';
const APP = ROOT . '/' . APP_PATH_NAME;
const CORE = ROOT . '/core';
const HELPERS = ROOT . '/helpers';
const CONFIG = ROOT . '/config';

const VIEWS_PATH_NAME ='Views';
const VIEWS = APP . '/' . VIEWS_PATH_NAME;
const CACHE = ROOT . '/tmp/cache';
const LAYOUT = 'default';

const USE_TWIG_DEFAULT = false;
const THEME = '';
const PATH = '';

const USER_AVATAR = PATH . '/images/avatar_default.png';
const LOGIN_PAGE = PATH . '/login';
const DB = [
    'host' => 'localhost',
    'dbname' => 'dbname',
    'username' => 'dbusername',
    'password' => 'dbpassword',
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
];
