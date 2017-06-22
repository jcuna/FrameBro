<?php

namespace App\Controllers;

use App\Core\Http\View;

class HomeController extends Controller
{
    /**
     * Home page controller
     */
    public function index()
    {
        return View::render('home/index', []);
	}
}
