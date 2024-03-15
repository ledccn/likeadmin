<?php

/**
 * 中间件配置
 */

return [
    'likeadmin' => [
        Ledc\Likeadmin\Middleware\LoginMiddleware::class,
    ],
    'like' => [
        Ledc\Likeadmin\Middleware\LoginMiddleware::class,
    ],
];
