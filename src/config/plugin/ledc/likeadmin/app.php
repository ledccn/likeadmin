<?php

/**
 * 插件配置
 */

return [
    'enable' => true,
    // 未登录时的固定返回码
    'token_invalid_code' => -1,
    // 失败code
    'fail_code' => 0,
    // 成功code
    'success_code' => 1,

    // 商城用户token（登录令牌）配置
    'user_token' => [
        'expire_duration' => 3600 * 24 * 30,    //用户token过期时长(单位秒）
        'be_expire_duration' => 3600,   //用户token临时过期前时长，自动续期
    ],
];
