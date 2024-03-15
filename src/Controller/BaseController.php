<?php

namespace Ledc\Likeadmin\Controller;

use Ledc\Likeadmin\JsonResponse;
use Ledc\Likeadmin\JsonResponseInterface;
use support\Container;
use support\Model;
use support\Response;

/**
 * 基础控制器
 */
class BaseController
{
    /**
     * @var Model
     */
    protected $model = null;

    /**
     * 无需登录及鉴权的方法
     * @var array
     */
    protected array $noNeedLogin = [];

    /**
     * 需要登录无需鉴权的方法
     * @var array
     */
    protected array $noNeedAuth = [];

    /**
     * 数据限制
     * - 例如：当$dataLimit='personal'时将只返回当前用户的数据
     * @var string|null
     */
    protected string|null $dataLimit = null;

    /**
     * 数据限制字段
     */
    protected string $dataLimitField = 'user_id';

    /**
     * JsonResponse服务
     * @return JsonResponse
     */
    final protected function jsonResponse(): JsonResponseInterface
    {
        return Container::has(JsonResponseInterface::class) ? Container::get(JsonResponseInterface::class) : Container::get(JsonResponse::class);
    }

    /**
     * 返回格式化json数据
     * @param int $code 响应码
     * @param string $msg 提示语
     * @param array $data 数据
     * @param int $show 前端是否显示弹窗：1显示弹窗、0静默
     * @return Response
     */
    final protected function json(int $code, string $msg = 'ok', array $data = [], int $show = 0): Response
    {
        return $this->jsonResponse()->json(... func_get_args());
    }

    /**
     * 成功响应
     * @param string $msg 提示语
     * @param array $data 数据
     * @param int $show 前端是否显示弹窗：1显示弹窗、0静默
     * @return Response
     */
    final protected function success(string $msg = 'success', array $data = [], int $show = 0): Response
    {
        return $this->jsonResponse()->success(... func_get_args());
    }

    /**
     * 成功响应
     * @param array $data 数据
     * @param int $show 前端是否显示弹窗：1显示弹窗、0静默
     * @return Response
     */
    final protected function data(array $data = [], int $show = 0): Response
    {
        return $this->jsonResponse()->success('', $data, $show);
    }

    /**
     * 失败响应
     * @param string $msg 提示语
     * @param array $data 数据
     * @param int $show 前端是否显示弹窗：1显示弹窗、0静默
     * @return Response
     */
    final protected function fail(string $msg = 'fail', array $data = [], int $show = 1): Response
    {
        return $this->jsonResponse()->fail(... func_get_args());
    }
}
