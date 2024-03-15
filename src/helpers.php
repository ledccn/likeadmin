<?php

namespace Ledc\Likeadmin;

use Exception;
use Ledc\Likeadmin\Middleware\LoginMiddleware;
use function is_array;
use function session;

// 定义插件常量
defined('PLUGIN_LEDC_LIKEADMIN') || define('PLUGIN_LEDC_LIKEADMIN', 'plugin.ledc.likeadmin');


/**
 * 当前登录用户id
 * @return integer|null
 */
function like_user_id(): ?int
{
    return session(LoginMiddleware::SESSION_KEY . '.id');
}


/**
 * 当前登录用户
 * @param array|string|null $fields
 * @return array|mixed|null
 * @throws Exception
 */
function like_user(array|string $fields = null): mixed
{
    LoginMiddleware::refreshUserSession();
    if (!$user = session(LoginMiddleware::SESSION_KEY)) {
        return null;
    }
    if ($fields === null) {
        return $user;
    }
    if (is_array($fields)) {
        $results = [];
        foreach ($fields as $field) {
            $results[$field] = $user[$field] ?? null;
        }
        return $results;
    }
    return $user[$fields] ?? null;
}


/**
 * 生成nginx配置文件
 * @return string
 */
function generate_nginx_proxy_config(): string
{
    $conf = '';
    // PUSH推送
    $push_app_key = config('plugin.webman.push.app.app_key');
    if ($push_app_key) {
        $push_websocket_port = parse_url(config('plugin.webman.push.app.websocket'), PHP_URL_PORT);
        $conf .= PHP_EOL . <<<EOF
location /app/$push_app_key
{
  proxy_pass http://127.0.0.1:$push_websocket_port;
  proxy_http_version 1.1;
  proxy_set_header Upgrade \$http_upgrade;
  proxy_set_header Connection "Upgrade";
  proxy_set_header X-Real-IP \$remote_addr;
}

EOF;
    }

    $server_port = parse_url(config('server.listen'), PHP_URL_PORT);
    $rule = 'likeadmin|like';
    if ($keys = array_keys(config('plugin.ledc.likeadmin.middleware', []))) {
        $rule = implode('|', $keys);
    }
    $conf .= PHP_EOL . <<<EOF
location ^~ /
{
  proxy_set_header X-Real-IP \$remote_addr;
  proxy_set_header Host \$host;
  proxy_set_header X-Forwarded-Proto \$scheme;
  proxy_http_version 1.1;
  proxy_set_header Connection "";
  if (!-f \$request_filename){
    proxy_pass http://127.0.0.1:$server_port;
  }
}

location ~ ^/($rule)
{
  proxy_set_header X-Real-IP \$remote_addr;
  proxy_set_header Host \$host;
  proxy_set_header X-Forwarded-Proto \$scheme;
  proxy_http_version 1.1;
  proxy_set_header Connection "";
  if (!-f \$request_filename){
    proxy_pass http://127.0.0.1:$server_port;
  }
}

EOF;
    return $conf;
}
