<?php
/**
 * Created by PhpStorm.
 * User: Joql
 * Date: 2019/4/9
 * Time: 14:17
 */

require_once 'vendor/autoload.php';
require_once 'common.php';
session_start();

//自动加载
spl_autoload_register(function ($class_name) {
    $class_name = str_replace('\\', DIRECTORY_SEPARATOR, $class_name);
    require_once __DIR__.'/'.$class_name . '.php';
});