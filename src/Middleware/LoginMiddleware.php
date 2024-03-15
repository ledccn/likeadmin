<?php

namespace Ledc\Likeadmin\Middleware;

use Exception;
use Ledc\Likeadmin\Model\User;
use Ledc\Likeadmin\Model\UserSession;
use ReflectionClass;
use ReflectionException;
use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;
use function Ledc\Likeadmin\like_user_id;

/**
 * Like用户鉴权中间件
 * - 凭token初始化session
 */
class LoginMiddleware implements MiddlewareInterface
{
    /**
     * 存储在SESSION的键名
     */
    public const SESSION_KEY = 'la_user';
    /**
     * 无需登录及鉴权
     * - 路由向中间件传参的键名
     * - true不需要登录，false需要登录
     */
    public const noNeedLogin = 'noNeedLogin';

    /**
     * @param Request $request
     * @param callable $handler
     * @return Response
     * @throws Exception
     */
    public function process(Request $request, callable $handler): Response
    {
        $token = $request->header('token', $request->cookie('token'));
        if ($token && ctype_alnum($token) && strlen($token) <= 40) {
            $request->sessionId($token);
            if (!like_user_id()) {
                self::setUserInfo($token);
            }
        }

        $code = 0;
        $msg = '';
        $show = 0;
        if (!self::canAccess($request, $code, $msg, $show)) {
            $response = json(['code' => $code, 'msg' => $msg, 'show' => $show]);
        } else {
            $response = $request->method() === 'OPTIONS' ? response('') : $handler($request);
        }

        return $response;
    }

    /**
     * 判断是否有权限
     * @param Request $request
     * @param int $code
     * @param string $msg
     * @param int $show
     * @return bool
     * @throws Exception
     */
    protected static function canAccess(Request $request, int &$code = 0, string &$msg = '', int &$show = 0): bool
    {
        $controller = $request->controller;
        $action = $request->action;
        if ($controller) {
            try {
                $class = new ReflectionClass($controller);
                $properties = $class->getDefaultProperties();
                $noNeedLogin = $properties['noNeedLogin'] ?? [];
                if (in_array($action, $noNeedLogin) || in_array('*', $noNeedLogin)) {
                    return true;
                }
            } catch (ReflectionException $e) {
                $code = 404;
                $msg = '控制器不存在' . $e->getMessage();
                $show = 1;
                return false;
            }
        } else {
            // 无控制器信息说明是函数调用，函数不属于任何控制器，鉴权操作应该在函数内部完成。
            // 默认路由 $request->route为null，所以需要判断 $request->route 是否为空
            $route = $request->route;
            if (!$route) {
                return true;
            }
            //路由是否需要验证登录
            if ($route->param(self::noNeedLogin)) {
                //路由不需要登录
                return true;
            }
        }

        //获取登录信息
        $user = session(static::SESSION_KEY);
        if (!$user) {
            // 未登录返回码
            $code = config(PLUGIN_LEDC_LIKEADMIN. '.app.token_invalid_code', -1);
            $msg = '未登录';
            return false;
        }

        $expire_time = $user['expire_time'] ?? 0;
        if (time() > $expire_time) {
            // 未登录返回码
            $code = config(PLUGIN_LEDC_LIKEADMIN. '.app.token_invalid_code', -1);
            $msg = '登录过期';
            $session = request()->session();
            UserSession::expireToken($session->getId());
            $session->forget(static::SESSION_KEY);
            return false;
        }

        static::refreshUserSession();
        return true;
    }

    /**
     * 刷新当前用户session
     * @param bool $force 强制刷新
     * @return void
     * @throws Exception
     */
    final public static function refreshUserSession(bool $force = false): void
    {
        if (!like_user_id()) {
            return;
        }
        $time_now = time();
        // session在3600秒内不刷新
        $session_ttl = 3600;
        $session_last_update_time = session(static::SESSION_KEY. '.session_last_update_time', 0);
        if (!$force && $time_now - $session_last_update_time < $session_ttl) {
            return;
        }

        $session = request()->session();
        if (!UserSession::overtimeToken($session->getId(), config(PLUGIN_LEDC_LIKEADMIN . '.app.user_token.expire_duration', 604800))) {
            $session->forget(static::SESSION_KEY);
            return;
        }

        self::setUserInfo($session->getId());
    }

    /**
     * 通过有效token设置用户信息缓存
     * @param string $token
     * @return void
     * @throws Exception
     */
    final protected static function setUserInfo(string $token): void
    {
        $userSession = UserSession::firstByToken($token);
        if ($userSession instanceof UserSession) {
            $session = request()->session();
            $user = User::find($userSession->user_id);
            if (!$user) {
                $session->forget(static::SESSION_KEY);
                return;
            }

            $userInfo = [
                'id' => $user->id,
                'user_id' => $user->id,
                'nickname' => $user->nickname,
                'token' => $token,
                'sn' => $user->sn,
                'mobile' => $user->mobile,
                'avatar' => $user->avatar,
                'terminal' => $userSession->terminal,
                'expire_time' => $userSession->expire_time,
                'session_last_update_time' => time(),
            ];
            $session->set(static::SESSION_KEY, $userInfo);
        }
    }
}
