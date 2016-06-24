<?php

/**
 * Author: Jon Garcia
 * Date: 5/29/16
 */

namespace App\Migrations;

use App\Core\Migrations\Migrations;
use app\core\migrations\Table;

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
    }

    public function down() {
        $this->drop('users');
    }
}