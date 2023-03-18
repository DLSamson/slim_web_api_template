<?php

require_once dirname(__DIR__).'/vendor/autoload.php';
require_once dirname(__DIR__).'/config/bootstrap.php';

use Illuminate\Database\Capsule\Manager as Capsule;

/* Create your tables */

//Capsule::table('users', function (\Illuminate\Database\Schema\Blueprint $table) {
//    $table->id();
//
//    $table->string('username');
//    $table->string('email');
//    $table->string('password');
//
//    $table->timestamps();
//    $table->softDeletes();
//});