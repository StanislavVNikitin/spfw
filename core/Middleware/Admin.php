<?php

namespace PHPFramework\Middleware;

class Admin
{
    public function handle():void
    {
        if (!is_admin()){
            session()->setFlash('error','Forbidden');
            response()->redirect(base_url('/'));
        }
    }

}