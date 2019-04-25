  ## 简介 
 原生php,composer包引入式开发.满足不需要框架,简单需要的客户

  ## composer包
 + url解析包 composer require riimu/kit-urlparser
 + tporm包  composer require topthink/think-orm
 + tp模板引擎包  composer require topthink/think-template
 
  ## 伪静态
  - nginx
    ```nginx
    if (!-e $request_filename) {
       rewrite  ^(.*)$  /index.php/$1  last;
       break;
    }
    ```
  - apache
    ```apacheconfig
    <IfModule mod_rewrite.c>
        Options +FollowSymlinks -Multiviews
        RewriteEngine on
    
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteRule ^(.*)$ index.php/$1 [QSA,PT,L]
    </IfModule>
    ```
  ## 路由
  案例 http://example.com/index/index?act=123
  域名/控制器/操作?参数 
  ##    
  ## 快捷函数
  ### session($key, $val)