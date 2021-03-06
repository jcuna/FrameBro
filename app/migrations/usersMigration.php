<?php

/**
 * Author: Jon Garcia
 * Date: 5/29/16
 */

namespace App\Migrations;

use App\Core\Migrations\Migrations;
use app\core\migrations\Table;
use App\Models\Role;

class usersMigration extends Migrations
{

    public function up()
    {
        $this->create('users', function(Table $t) {

            $t->incremental('uid')->unsigned();
            $t->char('username', 64)->index();
            $t->char('password', 64);
            $t->string('fname', 64);
            $t->string('lname', 64);
            $t->string('login_token', 64)->null();
            $t->timestamps();
        });

        $this->update('users', function(Table $t) {

            $t->string('email', 64)->unique();
            $t->change()->string('login_token', 64)->index();

        });

        $this->create('roles', function(Table $t) {
            $t->incremental();
            $t->char('name');
            $t->timestamps();
        });

        // join table
        $this->create('roles_users', function (Table $t) {
            $t->incremental();
            $t->integer('user_id')->unsigned();
            $t->integer('role_id');
        });

        $this->update('roles_users', function (Table $t) {
            $t->foreign('roles');
            $t->foreign('users', 'uid');
        });

        //add values to role table
        $roles = (new Role())->all();
        if ($roles->isEmpty()) {
            $roles = [
                ['name' => 'Super Admin'],
                ['name' => 'Admin'],
                ['name' => 'Authenticated User']
            ];

            $role = new Role();
            $role->insert($roles);
        }
    }

    public function down() {
        $this->drop('users');
        $this->drop('roles');
        $this->drop('roles_users');
    }
}