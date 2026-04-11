<?php

namespace Hunjian\AliyunImsMixcut\Contracts;

/**
 * 可数组化接口
 *
 * 为时间线结构提供稳定的数组序列化。
 */
interface ArrayableInterface
{
    /**
     * 将对象转换为数组。
     *
     * @return array
     */
    public function toArray();
}
