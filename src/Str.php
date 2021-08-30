<?php

/**
 * Str
 *
 * @package    EntBoss
 * @copyright  Copyright (c) 2019 EntBoss (http://www.entboss.com)
 * @license    http://www.entboss.com/license
 * @author     EntBoss Team
 * @version    19.10.10
 *
 */

namespace Eb\Support;

class Str extends \Illuminate\Support\Str
{
    /**
     * 获取参数的值
     *
     * @param array $para
     * @param string $key
     * @param string $ext_key
     * @param bool $req
     * @return array|string
     */
    public static function p($para, $key, $ext_key = '', $req = false)
    {
        $value = '';
        if (isset($para[$key])) {
            $value = $para[$key];
        }
        if ($ext_key && isset($para[$ext_key])) {
            $ext_value = $para[$ext_key];
            if (!$value && $ext_value) {
                $value = $ext_value;
            }
        }
        if (!$value && $req) {
            $value = \Request::input($key) ?? '';
        }

        if (is_string($value)) {
            $value = trim($value);
            $value === '0' && $value = 0;
        } else if (is_array($value)) {
            $value = array_filter($value);
        }

        return $value || $value === 0 ? $value : '';
    }

    /**
     * 获取参数的值
     *
     * @param array $para
     * @param array $keys
     * @param array $except
     * @return array
     */
    public static function v($para, $keys, $except = [])
    {
        $arr = [];
        foreach ($keys as $key) {
            if (in_array($key, array_keys($para))) {
                if (!in_array($key, $except)) {
                    $arr[$key] = $para[$key];
                }
            }
        }
        return $arr;
    }

    /**
     * 获取 Uuid
     *
     */
    public static function getUuid($md5 = true)
    {
        $str = self::getUniqid();
        if ($md5) {
            $str = substr(md5($str), 8, 16);
        }
        return $str;
    }

    /**
     * 获取唯一值
     *
     */
    public static function getUniqid()
    {
        $str = Str::uuid()->toString();
        //$str = uniqid(md5(microtime(true)), true);
        return $str;
    }

    /**
     * 获取 Token
     *
     * @param string $str 指定字符串
     *
     * @return string
     */
    public static function getToken($str = '')
    {
        $str = md5(self::getUniqid() . $str);
        return $str;
    }

    /**
     * 获取模块验证值
     *
     * @param string $str 指定字符串
     *
     * @return string
     */
    public static function getModuleKey($str = '')
    {
        if (!$str) {
            $str = 'default';
        }
        $key = config('api.module_key')[$str] ?? $str;
        return md5($key . date('Ymd'));
    }

    /**
     * 在数字编号前面补0，默认6位数
     * 0 => 000000,1 => 000001,20 => 000020,432 => 000432.
     *
     * @param int $num
     * @param int $n
     *
     * @return string
     */
    public static function getPadId($num, $n = 6)
    {
        return str_pad((int)$num, $n, '0', STR_PAD_LEFT);
    }

    /**
     * 生成订单编码
     * @param string $prefix
     * @return string
     */
    public static function getOrderNo($prefix = 'O')
    {
        return $prefix . ($prefix ? '-' : '') . date('ymd-His') . rand(100, 999);
    }

    /**
     * 字符串转成 url 地址
     */
    public static function toUrl($str)
    {
        $codes = Str::charsArray();
        foreach ($codes as $key => $value) {
            $value = implode("|", $value);
            $str = preg_replace("/($value)/i", $key, $str);
        }
        return strtolower(preg_replace(
            ['/[^a-zA-Z0-9\s-]/', '/[\s-]+|[-\s]+|[--]+/', '/^[-\s_]|[-_\s]$/'],
            ['', '-', ''],
            strtolower($str)
        ));
    }

    /**
     * 字符串转成数组
     */
    public static function toArray($str, $separator = ',')
    {
        if (is_array($str)) {
            return $str;
        }
        if (is_object($str)) {
            return Arr::objectToArray($str);
        }
        if (is_string($str)) {
            return explode($separator, $str);
        }
    }

    /**
     * 去除html标签
     *
     * @param string $string
     * @param string $allow 允许的标签,如:'<br>'
     * @return string
     */
    public static function clearHtml($string, $allow = '')
    {
        $string = strip_tags($string, $allow);
        $string = trim($string);
        $string = str_replace("\t", "", $string);
        $string = str_replace("\r\n", "", $string);
        $string = str_replace("\r", "", $string);
        $string = str_replace("\n", "", $string);
        $string = str_replace(" ", "", $string);
        $string = str_replace("&nbsp;", "", $string);
        return trim($string);
    }

    /**
     * 为字符串添加前缀-一维数组
     *
     * @param array $data 数据源
     * @param string $file 导出文件名
     * @return array
     */
    public static function setStrPrefix($data = [], $prefix = '')
    {
        if (empty($data) || empty($prefix)) {
            return $data;
        }
        $data = array_map(function ($v) use ($prefix) {
            return $prefix . $v;
        }, $data);
        return $data;
    }

    /**
     * 生成64位随机字符
     * @return string
     */
    public static function generateRandomChars()
    {
        $str = md5(time() . rand(100000, 999999));
        $str .= md5(microtime(true) . rand(100000, 999999));
        return strtoupper($str);
    }

    /**
     * 过滤特殊字符
     * @param $str
     * @param string $delimiter
     * @return mixed|string
     */
    public static function filterSpecial($str, $delimiter = '_')
    {
        if (empty($str)) return '';

        $searchArr = [' & ', '&', '%', '/', '\\', "\r\n", "\r", "\n", ' ', '--'];
        $replaceArr = array_pad([], count($searchArr), $delimiter ?: '-');

        $str = str_replace($searchArr, $replaceArr, $str);
        return $str;
    }
}
