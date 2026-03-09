<?php

namespace App\Controllers;

use PHPFramework\Pagination;

class HomeController extends BaseController
{
    public function index()
    {
       return view('home/index',[
           'title' => 'SPFW - Simple PHP Framework',
       ]);
    }
}

