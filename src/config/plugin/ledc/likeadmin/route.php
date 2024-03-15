<?php

/**
 * 路由配置
 * @link https://www.workerman.net/doc/webman/route.html
 */

use support\Request;
use Webman\Route;
use function Ledc\Likeadmin\generate_nginx_proxy_config;

Route::get('/plugin/ledc/likeadmin/nginx', function (Request $request) {
    return response(generate_nginx_proxy_config());
});
//关闭默认路由
//Route::disableDefaultRoute();
