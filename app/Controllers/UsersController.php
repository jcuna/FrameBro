<?php
/**
 * Created By: Jon Garcia
 * Date: 1/16/16
 **/
namespace App\Controllers;

use App\Core\Exceptions\ModelException;
use App\Models\Role;
use App\Models\User;
use App\Core\Http\View;
use App\Core\Request;

class UsersController extends Controller
{

    /**
     * @param string $username
     * @return string
     */
    public function index ($username = '') {

        $params = \App::getRequest();

        $this->validate($params, [
            'current_password' => ['required'],
            'new_password' => ['required', 'minimum:5'],
            'new_password_repeat' => ['sameAs:new_password']
        ]);

        if ($this->validated) {
            $user = new User();
            $user->where('username', View::getUser('username'))->get(['password', 'uid']);

            if (password_verify($params->current_password, $user->password)) {
                $user->password = User::encrypt($params->new_password);
                if ($user->save()) {
                    View::info('Password was updated successfully');
                    $this->redirect('/users');
                    exit;
                }
            } else {
                View::error('Wrong password');
            }
        }

        $this->displayErrors();

        if ($this->isLoggedIn()) {
            if (empty($username)) {
                $username = View::getUser('username');
                $user = new User();
                $user->where('username', $username)->get();
            }
            else {
                $user = new User();
                $user->with('roles')->where('username', $username)
                    ->groupConcat(['roles.name' => 'roles'])->get(['users.*']);
            }
            if ($user->count) {
                return View::render('user/view_user', ['user' => $user->attributes]);
            }
            else {
                return View::render('errors/error', 'Page not found', 404);
            }
        }
        else {
            $this->redirect('/login');
        }
    }

    /**
     * @param Request $params
     * @return string
     */
    public function create(Request $params)
    {
        if ($this->isLoggedIn() && View::hasRole(['Super Admin', 'Admin'])) {

            $this->validate($params, [
                'username' => ['required', 'unique:users', 'minimum:4'],
                'email' => ['required', 'email', 'unique:users', 'message' => 'Invalid email address'],
                'first-name' => ['required'],
                'last-name' => ['required'],
                'roles' => ['required'],
                'password' => ['required', 'minimum:6'],
                'repeat-password' => ['required', 'sameAs:password']
            ]);

            if ($this->validated) {
                $user = new User();

                $user->username = $params->username;
                $user->fname = $params->{'first-name'};
                $user->lname = $params->{'last-name'};
                $user->email = $params->email;
                $user->password = User::encrypt($params->password);

                if ($user->save()) {
                    if ($user->morphTo('roles')->save([ 'user_id' => $user->lastId, 'role_id' => $params->roles ])) {
                        View::info('User successfully created');
                        $this->redirect('/users/create');
                    }
                } else {
                    View::error('An error occurred and we couldn\'t save the user');
                    $this->redirect('/users/create');
                }

            } else {

                $this->displayFirstError();

                $roles = (new Role())->all();

                $arRoles = array();
                foreach ($roles as $key => $attribute) {
                    if (!View::hasRole('Super Admin') && $attribute->name === 'Super Admin') {
                        unset($attribute->name);
                    }
                    else {
                        $arRoles[$attribute->id] = $attribute->name;
                    }
                }

                return View::render('user/create', $arRoles);
            }
        }

        else {
            View::error('You don\'t have access to create users.');
            $this->redirect('/');
        }
    }

    /**
     * @throws \Exception
     */
    public function login(Request $params)
    {
        if ($params->getHttpMethod() === "POST") {
            $this->validate($params, [
                'username' => ['required'],
                'password' => ['required', 'minimum:6']
            ]);
        }

        if ($this->validated) {
            $user = new User();

            try {
                $user->with('roles')
                    ->where('username', $params->username)
                    ->groupConcat(['roles.name' => 'roles'])
                    ->get(['users.*']);
            } catch (ModelException $exception) {
                if ($exception->getCode() === 1146) {
                    \Kint::$display_called_from = false;
                    s("`users` table does not seem to exist, did you run migrations?");
                    sd("Navigate to " . ABSOLUTE_PATH . " and do `php framebro` in the terminal for more info");
                } else {
                    throw $exception;
                }
            }

            if ($user->count && $user->Authenticate($params)) {
                View::info('Eureka!!');
            } else {
                View::error('Wrong username/password combination');
            }
        }

        $this->displayErrors();

        if ($this->isLoggedIn()) {
            $this->redirect('/users');
        }

        return View::render('login/index', array());
    }

    /**
     *
     */
    public function tryLoginWithCookie()
    {
        $user = new User();
        $user->loginWithCookie();
    }

    /**
     *
     */
    public function logout()
    {
        $user = new User();
        if ( $user->logout() ) {
            $this->redirect('/');
        }
    }

    /**
     * @return string
     * @throws \App\Core\Exceptions\ModelException
     */
    public function allUsers()
    {
        $users = (new User())->all();
        return View::render('user/view_user', ['users' => $users]);
    }

    /**
     * @throws \Exception
     */
    public function deleteCurrentUser()
    {
        $params = new Request();
        if (View::hasRole(['Super Admin', 'Admin']) && $params->user !== View::getUser('username') ) {
            $user = new User();
            $user->where('username', $params->user)->get();
            //if this user has a record on the roles_user pivot table delete it.
            if ($role = $user->morphTo('roles')->where('user_id', $user->uid)->first()) {
                $role->delete();
            }
            if ($user->delete()) {
                if ($user->count == 1 ) {
                    return [
                        "status" => "success",
                        "content"  => '<div class=\'user-deleted well\'>User has been deleted successfully</div>'
                    ];
                }
                else {
                    return [
                        "status" => "fail",
                        "content"  => '<div class=\'user-not-deleted well\'>An unexpected problem occurred, please try again</div>'
                    ];
                }
            }
        } else {
            return [
                "status" => "fail",
                "content"  => '<div class=\'user-not-deleted well\'>You cannot delete yourself</div>'
            ];
        }
    }
}
