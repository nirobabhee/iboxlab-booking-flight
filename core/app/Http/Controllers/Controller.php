<?php

namespace App\Http\Controllers;


use Illuminate\Foundation\Validation\ValidatesRequests;

abstract class Controller
{
    use ValidatesRequests;

    public function __construct()
    {
        $className = get_called_class();
    }

    public static function middleware()
    {
        return [];
    }

}
