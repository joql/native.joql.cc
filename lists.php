<?php
use Joql\Api;
require_once 'init.php';

$api = new Api();
$act = $_GET['act'];
//action
switch ($act){
    case 'logoOut':
        return $api->logoOut();
    case 'save':
        return $api->save();
    case 'orderInfo':
        return $api->orderInfo();
    case 'orderStatus':
        return $api->orderStatus();
    case 'updateStatus':
        return $api->updateStatus();
}

//body
$api->lists_index();


?>
