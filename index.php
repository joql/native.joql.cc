<?php
use Joql\Api;
require_once 'init.php';

$api = new Api();
$act = $_GET['act'];//action
switch ($act){
    case 'checkKey':
        return $api->checkKey();
}

//body
$api->index_index();


?>
