<?php

namespace Hunjian\AliyunImsMixcut\Support;

use Hunjian\AliyunImsMixcut\Contracts\ArrayableInterface;

/**
 * 数组辅助类
 *
 * 将嵌套结构规范化为稳定的请求数组。
 */
class ArrayHelper
{
    /**
     * 序列化任意值。
     *
     * @param mixed $value
     *
     * @return mixed
     */
    public static function serialize($value)
    {
        if ($value instanceof ArrayableInterface) {
            return $value->toArray();
        }

        if (is_array($value)) {
            $result = array();
            foreach ($value as $key => $item) {
                $result[$key] = self::serialize($item);
            }

            return self::removeNullValues($result);
        }

        return $value;
    }

    /**
     * Remove null values recursively while preserving 0, false and empty string.
     *
     * @param array $data
     *
     * @return array
     */
    public static function removeNullValues(array $data)
    {
        $result = array();

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = self::removeNullValues($value);
            }

            if ($value !== null) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * 将显式字段与扩展字段合并。
     *
     * @param array $base
     * @param array $extra
     *
     * @return array
     */
    public static function mergeExtra(array $base, array $extra)
    {
        if (empty($extra)) {
            return self::removeNullValues($base);
        }

        foreach ($extra as $key => $value) {
            $base[$key] = self::serialize($value);
        }

        return self::removeNullValues($base);
    }

    /**
     * Determine whether an array is associative.
     *
     * @param array $data
     *
     * @return bool
     */
    public static function isAssoc(array $data)
    {
        return array_keys($data) !== range(0, count($data) - 1);
    }
}
