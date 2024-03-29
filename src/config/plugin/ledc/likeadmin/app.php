<?php

/**
 * 插件配置
 */

return [
    'enable' => true,
    // 未登录时，响应code
    'token_invalid_code' => -1,
    // 失败响应code
    'fail_code' => 0,
    // 成功响应code
    'success_code' => 1,

    // 商城用户token（登录令牌）配置
    'user_token' => [
        'expire_duration' => 3600 * 24 * 30,    //用户token过期时长(单位秒）
    ],
];
