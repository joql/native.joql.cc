<?php
/**
 * Created by PhpStorm.
 * User: Joql
 * Date: 2019/4/9
 * Time: 13:53
 */
namespace Joql;


use think\Db;
use think\Validate;

class Lists extends Base
{

    public function __construct()
    {
        parent::__construct();
    }

    public function index(){
        $this->templage->fetch('lists/index');
    }
}