<?php

namespace Hunjian\AliyunImsMixcut\Support;

use Hunjian\AliyunImsMixcut\Exception\SerializationException;

/**
 * Class Json
 *
 * Centralized JSON encoding/decoding with meaningful exceptions.
 */
class Json
{
    /**
     * 将值编码为 JSON。
     *
     * @param mixed $value
     * @param bool  $pretty
     *
     * @return string
     */
    public static function encode($value, $pretty = false)
    {
        $options = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

        if ($pretty) {
            $options = $options | JSON_PRETTY_PRINT;
        }

        $json = json_encode($value, $options);

        if ($json === false) {
            throw new SerializationException('JSON encode failed: ' . json_last_error_msg());
        }

        return $json;
    }

    /**
     * Decode JSON string to array.
     *
     * @param string $json
     *
     * @return array
     */
    public static function decode($json)
    {
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new SerializationException('JSON decode failed: ' . json_last_error_msg());
        }

        return is_array($data) ? $data : array();
    }
}
