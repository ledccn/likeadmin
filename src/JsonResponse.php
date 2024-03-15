<?php

namespace Ledc\Likeadmin;

use support\Response;

/**
 * JsonResponse服务
 */
readonly class JsonResponse implements JsonResponseInterface
{
    /**
     * 构造函数
     * @param int $successCode 成功响应码
     * @param int $failCode 失败响应码
     */
    public function __construct(protected int $successCode = 1, protected int $failCode = 0)
    {
    }

    /**
     * 返回格式化json数据
     * @param int $code 响应码
     * @param string $msg 提示语
     * @param array $data 数据
     * @param int $show 前端是否显示弹窗：1显示弹窗、0静默
     * @return Response
     */
    final public function json(int $code, string $msg = 'ok', array $data = [], int $show = 0): Response
    {
        return json(['code' => $code, 'data' => $data, 'msg' => $msg, 'show' => $show]);
    }

    /**
     * 成功响应数据
     * @param array $data
     * @return Response
     */
    final public function data(array $data): Response
    {
        return $this->success('success', $data);
    }

    /**
     * 成功响应
     * @param string $msg 提示语
     * @param array $data 数据
     * @param int $show 前端是否显示弹窗：1显示弹窗、0静默
     * @return Response
     */
    final public function success(string $msg = 'success', array $data = [], int $show = 0): Response
    {
        return $this->json($this->successCode, $msg, $data, $show);
    }

    /**
     * 失败响应
     * @param string $msg 提示语
     * @param array $data 数据
     * @param int $show 前端是否显示弹窗：1显示弹窗、0静默
     * @return Response
     */
    final public function fail(string $msg = 'fail', array $data = [], int $show = 1): Response
    {
        return $this->json($this->failCode, $msg, $data, $show);
    }
}
