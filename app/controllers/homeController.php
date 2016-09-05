<?php

namespace App\Controllers;

use App\Core\Http\Controller;
use App\Core\View;
use App\Models\User;

class homeController extends Controller
{
    public function index() {

        $this->createUserIfNoneExists();
        $this->redirect('admin/statusReport');
    }

    /**
     * Create a user if no user has been created.
     * @throws \PDOException
     */
    private function createUserIfNoneExists()
    {
        try {
            $user = new User();

            If ($user->count() === 0) {
                $this->redirect('admin/firstUser');
            }
        } catch (\Exception $e) {
            $output = "Create a database, update the .env file and run migrations to use the built-in authentication,
            or remove the code inside the index method in the homeController and create your own";
            return View::render('admin.index', "<h2>".$output."</h2>");
        }
    }
}
