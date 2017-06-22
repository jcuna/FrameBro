<?php
/**
 * Created by PhpStorm.
 * Author: Jon Garcia
 * Date: 1/18/16
 */

namespace App\Controllers;

use App\Core\Cache\Cache;
use App\Core\Request;
use App\Core\Http\Routing\Router;
use App\Core\FileHandler\FileUploads;
use App\Core\Http\View;
use App\Models\Role;
use App\Models\User;

class AdminController extends Controller
{
    /**
     * @throws \Exception
     */
    public function index()
    {
        $params = new Request();

        if ($this->isLoggedIn()) {

            $this->validate($params, [
                'image' => ['required']
            ]);

            if ($this->validated) {
                FileUploads::upload('image', 'images/uploads');
            }

            $files = $this->getDirectoryFiles( FILES_PATH . 'images/uploads');

            ajaxRequest(
                array(
                    'callback' => 'deleteFiles',
                    'selector' => '.delete-file',
                    'event' => 'click',
                    'wrapper' => '#file-manager',
                )
            );

            return View::render('admin/file_upload', $files);
        }
        else {
            $this->redirect('/');
        }
    }

    /**
     * @return string
     */
    public function deleteFiles( ) {
        $params = new Request();
        $params = $params->all();
        if (unlink(PUBLIC_PATH . $params['element']['data-collect'])) {
            View::info('File deleted successfully');
        } else {
            View::error('File could not be deleted, make sure you have the proper permissions.');
        }
        $files = $this->getDirectoryFiles( FILES_PATH . 'images/uploads');
        return View::render('admin/file_upload', $files);
    }

    /**
     *
     */
    public function showRoutes()
    {
        if ($this->isLoggedIn()) {
            \Kint::$display_called_from = false;
            sd(Router::getRoutes());
        }
    }

    /**
     *
     */
    public function memcachedStats()
    {
        if ($this->isLoggedIn()) {
            \Kint::$display_called_from = false;
            $mem = new Cache();
            sd($mem->stats());
        }
    }

    /**
     * @throws \Exception
     */
    public function logs()
    {
        if ($this->isLoggedIn()) {

            $result = '<h2>PHP Errors</h2><ul class="error-log-list">';

            $log = STORAGE_PATH . '/logging/app-errors.log';
            if (file_exists($log)) {
                $errorTypes = ['notice', 'warning', 'fatal', 'parse', 'exception'];
                $file = file($log);
                $file = array_reverse($file);

                while (list($var, $val) = each($file)) {
                    foreach ($errorTypes as $pattern) {
                        if (preg_match('@' . $pattern . '@i', $val, $matches)) {
                            $class = strtolower($matches[0]);
                            $result .= '<li class="error-log-line ' . $class . '">' . ($var+1) . '-  ' . $val . '</li>';
                        }
                    }
                }
            }
            $result .= '</ul>';

            return View::render('admin/index', $result);
        }
        else {
            return View::render('errors/error', 'Access denied', '443');
        }
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function info()
    {
        if ($this->isLoggedIn()) {
            return phpinfo();
        } else {
            return View::render('errors/error', 'Access denied', '443');
        }
    }

    /**
     * @throws \Exception
     */
    public function statusReport() {
        if ($this->isLoggedIn()) {
            return View::render('admin/index', self::getViewData());
        } else {
            return View::render('errors/error', 'Access denied', '443');
        }
    }

    /**
     * @return string
     */
    public static function getViewData(): string
    {
        $arrThingsToCheck = self::thingsToCheck();
        $result = '<h2>Status Report</h2><ul class="error-log-list">';

        foreach ($arrThingsToCheck as $v) {
            if ($v['value']) {
                $class = 'status-page passed';
                $result .= '<li class="error-log-line ' . $class . '">' . $v['name'] . ' is ' . $v['message'] . ' ' . $v['info'] . '</li>';
            } else {
                $class = 'status-page failed';
                $result .= '<li class="error-log-line ' . $class . '">' . $v['name'] . ' not ' . $v['message'] . ' ' . $v['info'] . '</li>';
            }
        }
        $result .= '</ul>';

        return $result;
    }

    public static function thingsToCheck(): array
    {
        $validPHP = (version_compare(PHP_VERSION, '7.0') === -1) ? false : true;
        $memcache = (class_exists('\Memcached'));
        return [
            'xattr' => [
                'name' => 'xattr Extension',
                'message' => 'Loaded',
                'value' => getenv('XATTR_SUPPORT'),
                'info' => '(Required to identify if a view has been updated)'
            ],
            'storageDirAccess' => [
                'name' => 'Storage Directory',
                'message' => 'Writable',
                'value' => is_writable(STORAGE_PATH),
                'info' => '| ' .STORAGE_PATH
            ],
            'filesDirAccess' => [
                'name' => 'Files Directory',
                'message' => 'Writable',
                'value' => is_writable(FILES_PATH),
                'info' => '| ' . FILES_PATH
            ],
            'osExtendedAttr' => [
                'name' => 'OS Extended Attributes',
                'message' => 'Supported or Enabled',
                'value' => getenv('XATTR_ENABLED'),
                'info' => ''
            ],
            'phpVersion' => [
                'name' => 'PHP Version',
                'message' => 'Valid',
                'value' => $validPHP,
                'info' => '| Version: ' . PHP_VERSION
            ],
            'Memcached' => [
                'name' => 'Memcached Extension',
                'message' => 'Loaded',
                'value' => $memcache,
                'info' => ''
            ],
            'SimpleXML' => [
                'name' => 'SimpleXML Library',
                'message' => 'Loaded',
                'value' => class_exists("\\SimpleXMLElement"),
                'info' => '(Required if using built-in ajax library)'
            ]
        ];
    }

    /**
     * Creates the first Super Admin user when no other users exists.
     * Method is very similar to the create method inside the user's controller
     * @throws \App\Core\Exceptions\ModelException
     * @throws \App\Core\Exceptions\ViewException
     */
    public function firstUser()
    {
        $user = new User();
        if ($user->count() === 0 ) {

            $params = new Request();

            $this->validate($params, [
                'username' => ['required', 'unique:users', 'minimum:5', 'message' => 'Username is not valid'],
                'email' => ['required', 'email'],
                'first-name' => ['required'],
                'last-name' => ['required'],
                'roles' => ['required'],
                'password' => ['required', 'minimum:6'],
                'repeat-password' => ['required', 'sameAs:password'],
            ]);

            if ($this->validated) {
                $user = new User();

                $user->username = $params->username;
                $user->fname = $params->{'first-name'};
                $user->lname = $params->{'last-name'};
                $user->email = $params->email;
                $user->password = $user->encrypt($params->password);

                if ($user->save()) {
                    if ($user->morphTo('roles')->save(['user_id' => $user->lastId, 'role_id' => $params->roles])) {
                        View::info('User successfully created');
                        $this->redirect('/login');
                    }
                } else {
                    View::error('An error occurred and we couldn\'t save the user');
                    $this->redirect('/');
                }

            } else {

                $this->displayFirstError();

                $roles = (new Role())->all();

                $arRoles = [];
                foreach ($roles as $attribute) {
                    if ($attribute->name !== 'Super Admin') {
                        unset($attribute->name);
                    } else {
                        $arRoles[$attribute->id] = $attribute->name;
                    }
                }

                return View::render('user/create', $arRoles);
            }
        } else {
            return View::render('errors/error', 'An unexpected error has occurred', 500);
        }
    }
}