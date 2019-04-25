<?php
/**
 * Created by PhpStorm.
 * User: Joql
 * Date: 2019/4/23
 * Time: 21:22
 */

function returnAjax($code, $msg = '', $data = array()){
    header('Content-Type:application/json; charset=utf-8');
    exit(json_encode(array('code' => $code, 'data' => $data, 'msg' => $msg)));
}

/**
 * use for:
 * auth: Joql
 * @param $str
 * @param string $default
 * @return string
 * date:2019-04-23 21:33
 */
function show($str, $default=''){
    if(!isset($str) || empty($str)){
        echo $default;
    }
    echo $str;
}

function cardPrice($sec){
    switch ($sec){
        case '3600':
            return [
                'type' => 94,
                'price' => 1,
            ];
        case '43200':
            return [
                'type' => 95,
                'price' => 10,
            ];
        case '86400':
            return [
                'type' => 96,
                'price' => 20,
            ];
        case '604800':
            return [
                'type' => 97,
                'price' => 40,
            ];
        case '1296000':
            return [
                'type' => 109,
                'price' => 80,
            ];
        case '2592000':
            return [
                'type' => 98,
                'price' => 120,
            ];
        default:
            return [
                'type' => 94,
                'price' => 1,
            ];
    }
}
function orderStatus($s){
    switch ($s){
        case '0':
            return 2;
        case '1':
            return 1;
        case '2':
            return 3;
        case '-1':
            return 4;
        case '3':
            return 5;
    }
}

function parseSecond($secs)
{
    if ($secs <= 0) {
        return '已到期';
    }
    $r = '';
    if ($secs >= 86400) {
        $days = floor($secs / 86400);
        $secs = $secs % 86400;
        $r = $days . '天 ';
    }
    if ($secs >= 3600) {
        $hours = floor($secs / 3600);
        $secs = $secs % 3600;
        $r .= $hours . '小时 ';
    }
    if ($secs >= 60) {
        $minutes = floor($secs / 60);
        $secs = $secs % 60;
        $r .= $minutes . '分';
    }
    //$r .= $secs . ' 秒';
    return $r;
}

/**
 * CURL请求
 * @param $url 请求url地址
 * @param $method 请求方法 get post
 * @param null $postfields post数据数组
 * @param array $headers 请求header信息
 * @param bool|false $debug  调试开启 默认false
 * @return mixed
 */
function httpRequest($url, $method, $postfields = null, $headers = array(), $debug = false) {
    $method = strtoupper($method);
    $ci = curl_init();
    /* Curl settings */
    curl_setopt($ci, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
    curl_setopt($ci, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.2; WOW64; rv:34.0) Gecko/20100101 Firefox/34.0");
    curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 60); /* 在发起连接前等待的时间，如果设置为0，则无限等待 */
    curl_setopt($ci, CURLOPT_TIMEOUT, 7); /* 设置cURL允许执行的最长秒数 */
    curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
    switch ($method) {
        case "POST":
            curl_setopt($ci, CURLOPT_POST, true);
            if (!empty($postfields)) {
                $tmpdatastr = is_array($postfields) ? http_build_query($postfields) : $postfields;
                curl_setopt($ci, CURLOPT_POSTFIELDS, $tmpdatastr);
            }
            break;
        default:
            curl_setopt($ci, CURLOPT_CUSTOMREQUEST, $method); /* //设置请求方式 */
            break;
    }
    $ssl = preg_match('/^https:\/\//i',$url) ? TRUE : FALSE;
    curl_setopt($ci, CURLOPT_URL, $url);
    if($ssl){
        curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
        curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, FALSE); // 不从证书中检查SSL加密算法是否存在
    }
    //curl_setopt($ci, CURLOPT_HEADER, true); /*启用时会将头文件的信息作为数据流输出*/
    //curl_setopt($ci, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ci, CURLOPT_MAXREDIRS, 2);/*指定最多的HTTP重定向的数量，这个选项是和CURLOPT_FOLLOWLOCATION一起使用的*/
    curl_setopt($ci, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ci, CURLINFO_HEADER_OUT, true);
    /*curl_setopt($ci, CURLOPT_COOKIE, $Cookiestr); * *COOKIE带过去** */
    $response = curl_exec($ci);
    $requestinfo = curl_getinfo($ci);
    $http_code = curl_getinfo($ci, CURLINFO_HTTP_CODE);
    if ($debug) {
        echo "=====post data======\r\n";
        var_dump($postfields);
        echo "=====info===== \r\n";
        print_r($requestinfo);
        echo "=====response=====\r\n";
        print_r($response);
    }
    curl_close($ci);
    return $response;
    //return array($http_code, $response,$requestinfo);
}

if (!function_exists('json')) {
    function json($data = [], $code = 200, $header = [], $options = [])
    {
        header('Content-Type:application/json; charset=utf-8');
        exit(json_encode($data));
    }
}

function session($key, $val=''){
    if(empty($val) && $val !== null){
        return $_SESSION[$key];
    }else if ($val === null){
        unset($_SESSION[$key]);
        return true;
    }else{
        $_SESSION[$key] = $val;
        return true;
    }
}
