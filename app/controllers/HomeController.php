<?php

namespace App\Controllers;

use App\Core\Http\Controller;
use App\Core\View;
use App\Models\Role;
use App\Models\User;

class homeController extends Controller
{
    public function index() {

        $this->createUserIfNoneExists();

        View::render('user.view_user');

    }

    /**
     * Create a user if no user has been created.
     * @throws \PDOException
     */
    private function createUserIfNoneExists()
    {
        $user = new User();

        If ($user->count() === 0) {
            $this->redirect('admin/firstUser');
        } else {
            return true;
        }
    }

}