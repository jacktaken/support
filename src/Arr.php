<?php

namespace Oosuite\Support;

class Arr extends \Illuminate\Support\Arr
{
    /**
     * 字符串转数组 - 并过滤恶意空值
     *
     * @param string|array $value 字符串|数组
     * @param bool $zero 是否需要保留整型0
     * @param string $delimiter 分割符
     * @return array
     * 示例：Arr::explodeFilter(['id'=>11,'name'=>'xue','age'=>0,'desc'=>'']
     */
    public static function explodeFilter($value, $zero = false, $delimiter = ',')
    {
        if (is_string($value) || is_numeric($value)) {
            $value = explode($delimiter, $value);
        }
        if (is_array($value)) {
            $value = array_filter($value, function ($v) use ($zero) {
                if ($v || ($zero && $v === 0)) {
                    return true;
                } else {
                    return false;
                }
            });
        }
        return $value;
    }

    /**
     * 数组转字符串
     *
     * @param string|array $value 字符串|数组
     * @param string $joiner 连接符
     * @return string
     */
    public static function implode($value, $joiner = ',')
    {
        if (is_array($value)) {
            $value = implode($joiner, $value);
        }
        if (is_string($value)) {
            $value = trim($value);
        }
        return $value;
    }

    /**
     * 数组转对象
     *
     * @param array $array
     * @return object
     */
    public static function arrayToObject($array)
    {
        return json_decode(json_encode($array), FALSE);
    }

    /**
     * 对象转数据
     *
     * @param object $object
     * @return array
     */
    public static function objectToArray($object)
    {
        return json_decode(json_encode($object), TRUE);
    }

    public static function JsonToArray($request)
    {
        $str = html_entity_decode($request);
        $json = json_decode($str);
        return self::objectToArray($json);
    }

    /**
     * 将接口获得的json数据字符串转换为php class类
     * echo Arr::jsonToClass('User', json_decode($user_json_str, true));
     */
    public static function jsonToClass($className, array $json_array)
    {
        $str = "<?php \n\n";
        $str .= "class {$className} {\n\n";
        if (count($json_array) > 0) {
            foreach ($json_array as $key => $value) {
                $type = gettype($value);
                if ($type == 'boolean') {
                    $_value = $value ? 'true' : 'false';
                } elseif ($type == 'string') {
                    $_value = "'{$value}'";
                } elseif (!in_array($type, array('object', 'array'))) {
                    $_value = $value;
                } else {
                    $_value = '';
                }
                $str .= "    /**\n";
                $str .= "     * @var {$type} \${$key} 如 {$_value}\n";
                $str .= "     */\n";
                $str .= "    public \${$key};\n\n";
            }
        }
        $str .= '}';
        return $str;
    }

    /**
     * 为数组每个元素的字符串添加前缀
     * @param array $data 数据源
     * @param string $prefix 前缀
     * @param string $joiner 连接符
     * @return array
     */
    public static function addPrefix($data = [], $prefix = '', $joiner = '.')
    {
        if (empty($data) || empty($prefix)) {
            return $data;
        }
        $prefix .= $joiner;
        $data = array_map(function ($v) use ($prefix) {
            return $prefix . $v;
        }, $data);
        return $data;
    }

    /**
     * 通过父ID串，获取到所有的子ID数组
     * 示例：Arr::getIdsFromParents('1',[
    ['id'=>4,'name'=>'xue','parents'=> '1,2'],
    ['id'=>5,'name'=>'xue','parents'=>'1,2,3,4',],
    ])
     */
    public static function getIdsFromParents($parent_ids, $arr, $parents_key = 'parents')
    {
        $data = [];
        if (!is_array($parent_ids)) {
            $parent_ids = explode(',', $parent_ids);
            $parent_ids = array_filter($parent_ids);
        }
        foreach ($arr as $item) {
            if (isset($item[$parents_key]) && $item[$parents_key]) {
                $parents_arr = explode(',', $item[$parents_key]);
                $parents_arr = array_filter($parents_arr);
                $result = array_intersect($parent_ids, $parents_arr);
                if (!empty($result)) {
                    $data[] = $item['id'] ?? '';
                }
            }
        }
        $data = array_merge($data, $parent_ids);
        $data = array_unique($data);
        return $data;
    }

    /**
     * 将第二个数组中剩余键的元素追加到第一个数组中
     * 示例：Arr::mergeTree(['id'=>11],['name'=>'xue'])
     * 结果：['id'=>11,'name'=>'xue']
     */
    public static function mergeTree(array $arr1, array $arr2): array
    {
        $res = $arr1 + $arr2;
        foreach (array_intersect_key($arr1, $arr2) as $k => $v) {
            if (is_array($v) && is_array($arr2[$k])) {
                $res[$k] = self::mergeTree($v, $arr2[$k]);
            }
        }
        return $res;
    }

    /**
     * 在数组中搜索给定的键，如果成功则返回偏移量
     * 示例：Arr::searchKey(['id'=>11,'name'=>'xue','age'=>0],'age')  返回2
     * 返回数字下标：id=0,name=1,age=2
     */
    public static function searchKey(array $arr, $key): ?int
    {
        $foo = [$key => null];
        return ($tmp = array_search(key($foo), array_keys($arr), true)) === false ? null : $tmp;
    }

    /**
     * 在key指定的项之前插入新数组
     * 示例：$arr = ['id'=>11,'name'=>'xue','age'=>18];
            Arr::insertBefore($arr,'age',['desc'=>'描述']);
     * 结果：['id'=>11,'name'=>'xue',['desc'=>'描述'],'age'=>18]
     */
    public static function insertBefore(array &$arr, $key, array $inserted): void
    {
        $offset = (int)self::searchKey($arr, $key);
        $arr = array_slice($arr, 0, $offset, true) + $inserted + array_slice($arr, $offset, count($arr), true);
    }

    /**
     * 在key指定的项之后插入新的数组
     */
    public static function insertAfter(array &$arr, $key, array $inserted): void
    {
        $offset = self::searchKey($arr, $key);
        $offset = $offset === null ? count($arr) : $offset + 1;
        $arr = array_slice($arr, 0, $offset, true) + $inserted + array_slice($arr, $offset, count($arr), true);
    }

    /**
     * 重命名数组中的关键字
     * 示例：$arr = ['id'=>11,'name'=>'xue','age'=>18];
            Arr::renameKey($arr,'age', 'ages');
     * 结果：['id'=>11,'name'=>'xue','ages'=>18]
     */
    public static function renameKey(array &$arr, $oldKey, $newKey): void
    {
        $offset = self::searchKey($arr, $oldKey);
        if ($offset !== null) {
            $keys = array_keys($arr);
            $keys[$offset] = $newKey;
            $arr = array_combine($keys, $arr);
        }
    }

    /**
     * 返回匹配模式的数组项
     * Returns array entries that match the pattern.
     */
    /*public static function grep(array $arr, string $pattern, int $flags = 0): array
    {
        return Strings::pcre('preg_grep', [$pattern, $arr, $flags]);
    }*/

    /**
     * 查找变量是否为基于数字的整数索引数组
     * 示例：Arr::isList([11,111,18])  返回true
     * 示例：Arr::isList(['id'=>1])    返回false
     */
    public static function isList($value): bool
    {
        return is_array($value) && (!$value || array_keys($value) === range(0, count($value) - 1));
    }

    /**
     * Reformats table to associative tree. Path looks like 'field|field[]field->field=field'.
     * @return array|\stdClass
     */
    /*public static function associate(array $arr, $path)
    {
        $parts = is_array($path)
            ? $path
            : preg_split('#(\[\]|->|=|\|)#', $path, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        if (!$parts || $parts[0] === '=' || $parts[0] === '|' || $parts === ['->']) {
            throw new Nette\InvalidArgumentException("Invalid path '$path'.");
        }

        $res = $parts[0] === '->' ? new \stdClass : [];

        foreach ($arr as $rowOrig) {
            $row = (array)$rowOrig;
            $x = &$res;

            for ($i = 0; $i < count($parts); $i++) {
                $part = $parts[$i];
                if ($part === '[]') {
                    $x = &$x[];
                } elseif ($part === '=') {
                    if (isset($parts[++$i])) {
                        $x = $row[$parts[$i]];
                        $row = null;
                    }
                } elseif ($part === '->') {
                    if (isset($parts[++$i])) {
                        $x = &$x->{$row[$parts[$i]]};
                    } else {
                        $row = is_object($rowOrig) ? $rowOrig : (object)$row;
                    }
                } elseif ($part !== '|') {
                    $x = &$x[(string)$row[$part]];
                }
            }

            if ($x === null) {
                $x = $row;
            }
        }

        return $res;
    }*/

    /**
     * 数组key是int类型，返回key为值，值为$filling的数组。
     * 示例：Arr::normalize([11,'xue',18],'111')  返回[11 =>"111","xue" =>"111",18 =>"111"]
     */
    public static function normalize(array $arr, $filling = null): array
    {
        $res = [];
        foreach ($arr as $k => $v) {
            $res[is_int($k) ? $v : $k] = is_int($k) ? $filling : $v;
        }
        return $res;
    }

    /**
     * 消除数组中某个key的值，不存在返回默认值
     * @throws \InvalidArgumentException 如果项不存在且未提供默认值
     * 示例：$arr = ['id'=>11,'name'=>'xue','age'=>18];
     *      Arr::pick($arr,'name');
     * 结果：["id" => 11,"age"=>18]   unset($arr['name']);
     *
     */
    public static function pick(array &$arr, $key, $default = null)
    {
        if (array_key_exists($key, $arr)) {
            $value = $arr[$key];
            unset($arr[$key]);
            return $value;
        } elseif (func_num_args() < 3) {
            throw new \InvalidArgumentException("Missing item '$key'.");
        } else {
            return $default;
        }
    }

    /**
     * 获取数组里面的id值，并以该值作为key生成新的数组.
     * 示例：Arr::getIdArray([['id'=>11,'name'=>'xue','hob'=>[['id'=>33,'name'=>'ll']]],['id'=>22,'name'=>'hh']])
     * 结果：[
        11 =>  [
            "name" => "xue"
            "hob" =>  [
                33 =>  ["name" =>"ll"]
           ]
        ],
        22 => ["name" => "hh"]
    ]  以id做为key
     */
    public static function getIdArray($arr)
    {
        $ret = [];
        if (is_array($arr) && count($arr)) {
            foreach ($arr as $item) {
                if (isset($item['id'])) {
                    $id = $item['id'];
                    unset($item['id']);
                    foreach ($item as &$repeat) {
                        if (is_array($repeat)) {
                            $repeat = self::getIdArray($repeat);
                        }
                    }
                    $ret[$id] = $item;
                }
            }
        }

        return $ret;
    }

    /**
     * 对数组进行重新分组，如groupBy操作.
     * 示例：Arr::arrayGroupBy([['id'=>11,'name'=>'xue'],['id'=>22,'name'=>'xue'],['id'=>33,'name'=>'hh']],'name')
     * 结果：[
                "xue" =>  [
                    "title" => "xue"
                    "item" =>  [
                        0 => ["id" => 11],
                        1 => ["id" => 22]
                    ]
                ],
                "hh" =>  [
                    "title" => "hh"
                    "item" =>  [
                        0 =>  ["id" => 33]
                    ]
                ]
            ]
     */
    public static function arrayGroupBy($array, $field)
    {
        $arr = [];
        foreach ($array as $item) {
            $name = $item[$field];
            unset($item[$field]);
            $arr[$name]['title'] = $name;
            $arr[$name]['item'][] = $item;
        }

        return $arr;
    }

    /**
     * 获取数组、对象下标对应值，不存在时返回指定的默认值
     * @param string|integer $name - 下标（键名）
     * @param array|object $data - 原始数组/对象
     * @param mixed $default - 指定默认值
     * 示例：Arr::getSubValue('name',['id'=>11,'name'=>'xue'])
     * 结果：'xue'
     */
    public static function getSubValue($name, $data, $default = '')
    {
        if (is_object($data)) {
            $value = isset($data->$name) ? $data->$name : $default;
        } else if (is_array($data)) {
            $value = isset($data[$name]) ? $data[$name] : $default;
        } else {
            $value = $default;
        }
        return $value;
    }

    /**
     * 比较数组是否相等
     *
     * @param array $arr1
     * @param array $arr2
     * @return boolean
     */
    public static function arraysEqual($arr1, $arr2)
    {
        if(count($arr1) != count($arr2)){
            return false;
        }else{
            $arrStr1 = serialize($arr1);
            $arrStr2 = serialize($arr2);
            if(strcmp($arrStr1, $arrStr2) == 0){
                return true;
            }else{
                return false;
            }
        }
    }

    /**
     * 找到一维数组中最长的字符串
     *
     * @param array $array
     * @return array
     * 示例：Arr::getLongStr(['id'=>11,'name'=>'xue'])
     * 结果：["xue"]
     */
    public static function getLongStr($array)
    {
        $strlen_arr = array_combine($array, array_map('strlen', $array));
        return array_keys($strlen_arr, max($strlen_arr));
    }

    /**
     * 找到多维数组中最长的字符串
     *
     * @param array $array
     * @return array
     * 示例：Arr::getMultiLongStr([['id'=>11,'name'=>'xue'],['id'=>12,'name'=>'xxxx']])
     * 结果：["xxxx"]
     */
    public static function getMultiLongStr($array)
    {
        $count_arr= [];
        $arr = array_walk_recursive($array, function ($arr) use (&$count_arr){  //主要利用函数array_walk_recursive()递归数组
            $count_arr = $count_arr + [$arr => strlen($arr)];
        });
        return array_keys($count_arr, max($count_arr));
    }

    /**
     * 过滤多维数组中 空白字符，false，null
     *
     * @param array $array
     * @param boolean $trim 是否对字符trim操作
     * @param boolean $unset_empty_arr 是否删除空的子数组
     * 示例：$arr = [['id'=>11,'name'=>'xue'],['id'=>12,'name'=>'']];
            Arr::arrayRemoveEmpty($arr);
     * 结果：[
                ["id" => "11","name" => "xue"],
                ["id" => "12"]
            ]
     */
    public static function arrayRemoveEmpty(&$array, $trim = true, $unset_empty_arr = false)
    {
        foreach ($array as $key => $value) {
            if(is_array($value)) {
                static::arrayRemoveEmpty($array[$key], $trim, $unset_empty_arr);
            }else{
                $value = trim($value);
                if ($value == '') {
                    unset($array[$key]);
                } elseif ($trim) {
                    $array[$key] = $value;
                }
            }
        }
        if(is_bool($unset_empty_arr) && $unset_empty_arr){
            $array = array_filter($array);
        }
        return $array;
    }

    /**
     * 数组中字段替换，只替换存在的键值，不会新增元素，支持多个数组替换
     * 区别于array_replce如果一个键存在于第二个数组，但是不存在于第一个数组，则会在第一个数组中创建这个元素
     *
     * @param array $array
     * @param array $cover_array 将要覆盖的数组
     * @return array
     * 示例：Arr::arrayReplace(['id'=>11,'name'=>'xue'],['name'=>'xxxx'])
     * 结果：["id" => 11,"name" => "xxxx"]   只替换存在的key
     */
    public static function arrayReplace($array, $cover_array)
    {
        // 返回$cover_array出现在$array的键名的值
        $key_intersect_arr = array_intersect_key($cover_array, $array);
        foreach ($key_intersect_arr as $key => $value) {
            $array[$key] = $value;// 替换赋值
        }
        if (func_num_args() > 2) {
            foreach (array_slice(func_get_args(), 2) as $cover_array) {
                foreach (array_intersect_key($cover_array, $array) as $key => $value) {
                    $array[$key] = $value;
                }
            }
        }
        return $array;
    }

    /**
     * 添加多个键值对到一个数组前面
     *
     * @param array $array
     * @param array $append_arr
     * @return array
     * 示例：Arr::arrPrepend(['id'=>11,'name'=>'xue'],['age'=>18,'hobby'=>'篮球'])
     * 结果：["hobby"=>"篮球","age"=>18,"id"=>11,"name"=>"xue"]
     */
    public static function arrPrepend($array, $append_arr)
    {
        if(!empty($append_arr)){
            foreach ($append_arr as $key => $val) {
                if(is_numeric($key)) {
                    array_unshift($array, $val);
                 }else {
                    $array = [$key => $val] + $array;
                 }
            }
        }
        return $array;
    }

    /**
     * 统计数组的深度
     *
     * @param array $array
     * @param int $level 初始位置
     * @return int
     * 示例：Arr::arraylevel([['id'=>11,'name'=>'xue']])
     * 返回 2
     */
    public static function arraylevel($array, &$level=0){
        if(is_array($array)){
            $level++;
            foreach ($array as $v){
                static::arraylevel($v, $level);
            }
        }
        return $level;
    }
}
