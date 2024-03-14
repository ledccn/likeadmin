<?php

namespace Ledc\Likeadmin;

use Exception;
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
    return session('la_user.id');
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
    if (!$user = session('la_user')) {
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
