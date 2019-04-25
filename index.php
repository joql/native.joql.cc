<?php

require_once 'init.php';

$complete_url = $_SERVER['REQUEST_SCHEME'].'://'. $_SERVER['HTTP_HOST']. $_SERVER['REQUEST_URI'];
$parser = new \Riimu\Kit\UrlParser\UriParser();
$uri = $parser->parse($complete_url);
$path = $uri->getPathSegments();
if(count($path) == 3){
    $control = '\Joql\\'.ucwords(strtolower($path[1]));
    $action = ucwords(strtolower($path[2]));
}elseif (count($path) == 2){
    $control = '\Joql\\'.ucwords(strtolower($path[0]));
    $action = ucwords(strtolower($path[1]));
}else{
    die('fail');
}

//body
try{
    $api = new $control();
    $api->$action();
}catch (Exception $e){
    die($e->getMessage());
}
?>
