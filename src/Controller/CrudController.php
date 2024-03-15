<?php

namespace Ledc\Likeadmin\Controller;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use plugin\admin\app\common\Tree;
use plugin\admin\app\common\Util;
use support\exception\BusinessException;
use support\Model;
use support\Request;
use support\Response;
use function Ledc\Likeadmin\like_user_id;

/**
 * CRUD控制器
 */
class CrudController extends BaseController
{
    /**
     * 查询
     * @param Request $request
     * @return Response
     * @throws BusinessException
     */
    public function select(Request $request): Response
    {
        [$where, $format, $limit, $field, $order] = $this->selectInput($request);
        $query = $this->doSelect($where, $field, $order);
        return $this->doFormat($query, $format, $limit);
    }

    /**
     * 添加
     * @param Request $request
     * @return Response
     * @throws BusinessException
     */
    public function insert(Request $request): Response
    {
        $data = $this->insertInput($request);
        $id = $this->doInsert($data);
        return $this->success('ok', ['id' => $id]);
    }

    /**
     * 更新
     * @param Request $request
     * @return Response
     * @throws BusinessException
     */
    public function update(Request $request): Response
    {
        [$id, $data] = $this->updateInput($request);
        $this->doUpdate($id, $data);
        return $this->success();
    }

    /**
     * 删除
     * - 会触发模型事件
     * @param Request $request
     * @return Response
     * @throws BusinessException
     */
    public function delete(Request $request): Response
    {
        $count = 0;
        if ($ids = $this->deleteInput($request)) {
            $count = $this->model->destroy($ids);
        }
        return $this->success('ok', ['count' => $count]);
    }

    /**
     * 获取完整的数据表名称
     * @return string
     */
    final protected function getTableName(): string
    {
        return config('plugin.admin.database.connections.mysql.prefix') . $this->model->getTable();
    }

    /**
     * 查询前置
     * @param Request $request
     * @return array
     * @throws BusinessException
     */
    protected function selectInput(Request $request): array
    {
        $field = $request->get('field');
        $order = $request->get('order', 'asc');
        $format = $request->get('format', 'normal');
        $limit = (int)$request->get('limit', $format === 'tree' ? 1000 : 10);
        $limit = $limit <= 0 ? 10 : $limit;
        $order = $order === 'asc' ? 'asc' : 'desc';
        $where = $request->get();
        $page = (int)$request->get('page');
        $page = $page > 0 ? $page : 1;
        $table = $this->getTableName();

        $allow_column = Util::db()->select("desc `$table`");
        if (!$allow_column) {
            throw new BusinessException('表不存在');
        }
        $allow_column = array_column($allow_column, 'Field', 'Field');
        if (!in_array($field, $allow_column)) {
            $field = null;
        }
        foreach ($where as $column => $value) {
            if (
                $value === '' || !isset($allow_column[$column]) ||
                is_array($value) && (empty($value) || !in_array($value[0], ['null', 'not null']) && !isset($value[1]))
            ) {
                unset($where[$column]);
            }
        }
        // 按照数据限制字段返回数据
        if ($this->dataLimit === 'personal' || $this->dataLimit === 'auth') {
            $where[$this->dataLimitField] = like_user_id();
        }
        return [$where, $format, $limit, $field, $order, $page];
    }

    /**
     * 指定查询where条件,并没有真正的查询数据库操作
     * @param array $where
     * @param string|null $field
     * @param string $order
     * @return EloquentBuilder|QueryBuilder|Model
     */
    protected function doSelect(array $where, string $field = null, string $order = 'desc'): EloquentBuilder|Model|QueryBuilder
    {
        //按主键降序排列
        if (empty($field) || empty($order)) {
            $field = $this->model->getKeyName();
            $order = 'desc';
        }

        $model = $this->model;
        foreach ($where as $column => $value) {
            if (is_array($value)) {
                if ($value[0] === 'like' || $value[0] === 'not like') {
                    $model = $model->where($column, $value[0], "%$value[1]%");
                } elseif (in_array($value[0], ['>', '=', '<', '<>'])) {
                    $model = $model->where($column, $value[0], $value[1]);
                } elseif ($value[0] == 'in' && !empty($value[1])) {
                    $valArr = $value[1];
                    if (is_string($value[1])) {
                        $valArr = explode(",", trim($value[1]));
                    }
                    $model = $model->whereIn($column, $valArr);
                } elseif ($value[0] == 'not in' && !empty($value[1])) {
                    $valArr = $value[1];
                    if (is_string($value[1])) {
                        $valArr = explode(",", trim($value[1]));
                    }
                    $model = $model->whereNotIn($column, $valArr);
                } elseif ($value[0] == 'null') {
                    $model = $model->whereNull($column);
                } elseif ($value[0] == 'not null') {
                    $model = $model->whereNotNull($column);
                } elseif ($value[0] !== '' || $value[1] !== '') {
                    $model = $model->whereBetween($column, $value);
                }
            } else {
                $model = $model->where($column, $value);
            }
        }
        if ($field) {
            $model = $model->orderBy($field, $order);
        }
        return $model;
    }

    /**
     * 执行真正查询，并返回格式化数据
     * @param $query
     * @param $format
     * @param $limit
     * @return Response
     */
    protected function doFormat($query, $format, $limit): Response
    {
        $methods = [
            'select' => 'formatSelect',
            'tree' => 'formatTree',
            'table_tree' => 'formatTableTree',
            'normal' => 'formatNormal',
        ];
        $paginator = $query->paginate($limit);
        $total = $paginator->total();
        $items = $paginator->items();
        if (method_exists($this, "afterQuery")) {
            $items = call_user_func([$this, "afterQuery"], $items);
        }
        $format_function = $methods[$format] ?? 'formatNormal';
        return call_user_func([$this, $format_function], $items, $total);
    }

    /**
     * 插入前置方法
     * @param Request $request
     * @return array
     * @throws BusinessException
     */
    protected function insertInput(Request $request): array
    {
        $data = $this->inputFilter($request->post());
        if ($this->dataLimit) {
            if (!empty($data[$this->dataLimitField])) {
                $data[$this->dataLimitField] = like_user_id();
            }
        }
        return $data;
    }

    /**
     * 执行插入
     * @param array $data
     * @return mixed|null
     */
    protected function doInsert(array $data): mixed
    {
        $primary_key = $this->model->getKeyName();
        $model_class = get_class($this->model);
        $model = new $model_class;
        foreach ($data as $key => $val) {
            $model->{$key} = $val;
        }
        $model->save();
        return $primary_key ? $model->$primary_key : null;
    }

    /**
     * 更新前置方法
     * @param Request $request
     * @return array
     * @throws BusinessException
     */
    protected function updateInput(Request $request): array
    {
        $primary_key = $this->model->getKeyName();
        $id = $request->post($primary_key);
        $data = $this->inputFilter($request->post());
        $model = $this->model->find($id);
        if (!$model) {
            throw new BusinessException('记录不存在', 2);
        }

        // 检查更新数据域是否有权限
        $dataLimitField = $this->dataLimitField;
        if ($this->dataLimit && !empty($data[$dataLimitField])) {
            if ($model->{$dataLimitField} === $data[$dataLimitField]) {
                throw new BusinessException('无数据权限');
            }
        }

        $password_filed = 'password';
        if (isset($data[$password_filed])) {
            // 密码为空，则不更新密码
            if ($data[$password_filed] === '') {
                unset($data[$password_filed]);
            }
        }
        unset($data[$primary_key]);
        return [$id, $data];
    }

    /**
     * 执行更新
     * @param $id
     * @param $data
     * @return void
     */
    protected function doUpdate($id, $data): void
    {
        $model = $this->model->find($id);
        foreach ($data as $key => $val) {
            $model->{$key} = $val;
        }
        $model->save();
    }

    /**
     * 对用户输入表单过滤
     * @param array $data
     * @return array
     * @throws BusinessException
     */
    protected function inputFilter(array $data): array
    {
        $table = $this->getTableName();
        $allow_column = $this->model->getConnection()->select("desc `$table`");
        if (!$allow_column) {
            throw new BusinessException('表不存在', 2);
        }
        $columns = array_column($allow_column, 'Type', 'Field');
        foreach ($data as $col => $item) {
            if (!isset($columns[$col])) {
                unset($data[$col]);
                continue;
            }
            // 非字符串类型传空则为null
            if ($item === '' && !str_contains(strtolower($columns[$col]), 'varchar') && !str_contains(strtolower($columns[$col]), 'text')) {
                $data[$col] = null;
            }
            // 数组list转字符串
            if (is_array($item) && array_is_list($item)) {
                $data[$col] = implode(',', $item);
            }
        }
        if (empty($data['created_at'])) {
            unset($data['created_at']);
        }
        if (empty($data['updated_at'])) {
            unset($data['updated_at']);
        }
        return $data;
    }

    /**
     * 删除前置方法
     * @param Request $request
     * @return array
     * @throws BusinessException
     */
    protected function deleteInput(Request $request): array
    {
        $primary_key = $this->model->getKeyName();
        if (!$primary_key) {
            throw new BusinessException('该表无主键，不支持删除');
        }
        $ids = (array)$request->post($primary_key, []);
        if ($this->dataLimit) {
            $admin_ids = $this->model->where($primary_key, $ids)->pluck($this->dataLimitField)->toArray();
            if (array_diff($admin_ids, [like_user_id()])) {
                throw new BusinessException('无数据权限');
            }
        }
        return $ids;
    }

    /**
     * 格式化树
     * @param $items
     * @return Response
     */
    protected function formatTree($items): Response
    {
        $format_items = [];
        foreach ($items as $item) {
            $format_items[] = [
                'name' => $item->title ?? $item->name ?? $item->id,
                'value' => (string)$item->id,
                'id' => $item->id,
                'pid' => $item->pid,
            ];
        }
        $tree = new Tree($format_items);
        return $this->success('ok', $tree->getTree());
    }

    /**
     * 格式化表格树
     * @param $items
     * @return Response
     */
    protected function formatTableTree($items): Response
    {
        $tree = new Tree($items);
        return $this->success('ok', $tree->getTree());
    }

    /**
     * 格式化下拉列表
     * @param $items
     * @return Response
     */
    protected function formatSelect($items): Response
    {
        $formatted_items = [];
        foreach ($items as $item) {
            $formatted_items[] = [
                'name' => $item->title ?? $item->name ?? $item->id,
                'value' => $item->id
            ];
        }
        return $this->success('ok', $formatted_items);
    }

    /**
     * 格式化下拉列表枚举
     * @param array $items
     * @return Response
     */
    protected function formatSelectEnum(array $items): Response
    {
        $formatted_items = [];
        foreach ($items as $name => $value) {
            $formatted_items[] = [
                'name' => $name,
                'value' => $value
            ];
        }
        return $this->success('ok', $formatted_items);
    }

    /**
     * 通用格式化
     * @param $items
     * @param $total
     * @return Response
     */
    protected function formatNormal($items, $total): Response
    {
        return json(['code' => 0, 'msg' => 'ok', 'count' => $total, 'data' => $items]);
    }

    /**
     * 查询数据库后置方法，可用于修改数据
     * @param mixed $items 原数据
     * @return mixed 修改后数据
     */
    protected function afterQuery(mixed $items): mixed
    {
        return $items;
    }
}