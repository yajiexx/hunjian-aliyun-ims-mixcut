<?php

namespace Hunjian\AliyunImsMixcut\Model\Effect;

/**
 * 滤镜效果类
 *
 * 片段级滤镜效果。
 */
class Filter extends Effect
{
    /**
     * 创建滤镜效果。
     *
     * @param string $subType
     * @param array  $params
     *
     * @return self
     */
    public static function make($subType, array $params = array())
    {
        return new self('Filter', $subType, $params);
    }
}
