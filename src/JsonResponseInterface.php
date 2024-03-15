<?php

namespace Ledc\Likeadmin;

use support\Response;

/**
 * JsonResponse接口
 */
interface JsonResponseInterface
{
    /**
     * 返回格式化json数据
     * @param int $code 响应码
     * @param string $msg 提示语
     * @param array $data 数据
     * @return Response
     */
    public function json(int $code, string $msg = 'ok', array $data = []): Response;

    /**
     * 成功响应
     * @param string $msg 提示语
     * @param array $data 数据
     * @return Response
     */
    public function success(string $msg = 'success', array $data = []): Response;

    /**
     * 失败响应
     * @param string $msg 提示语
     * @param array $data 数据
     * @return Response
     */
    public function fail(string $msg = 'fail', array $data = []): Response;

    /**
     * 成功响应数据
     * @param array $data
     * @return Response
     */
    public function data(array $data): Response;
}