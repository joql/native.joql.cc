<?php
/**
 * Created by PhpStorm.
 * User: Joql
 * Date: 2019/4/9
 * Time: 13:48
 */

namespace Joql;

use think\Db;
use think\Template;

class Base
{
    public  $db;
    protected $ROOT;//根目录
    public $templage; //模板引擎

    public function __construct()
    {
        $this->ROOT = dirname(dirname(__FILE__));
        $conf = include_once $this->ROOT . '/conf/config.php';
        $db_conf = include_once $this->ROOT . '/conf/database.php';

        //初始化数据库配置
        Db::setConfig($db_conf);
        //初始化模板
        $template_conf = [
            'view_path'     =>  $this->ROOT .'/'. $conf['view_path'],
            'cache_path'    =>  $this->ROOT .'/'. $conf['cache_path'],
            'view_suffix'   =>  $conf['view_suffix'],
        ];
        $this->templage = new Template($template_conf);
    }

}