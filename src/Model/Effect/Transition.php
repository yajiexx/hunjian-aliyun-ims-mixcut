<?php

namespace Hunjian\AliyunImsMixcut\Model\Effect;

/**
 * 转场效果类
 *
 * 片段级转场效果。
 */
class Transition extends Effect
{
    /**
     * 创建转场效果。
     *
     * @param string $subType
     * @param float  $duration
     * @param array  $params
     *
     * @return self
     */
    public static function make($subType, $duration, array $params = array())
    {
        return new self('Transition', $subType, array_merge(array(
            'Duration' => $duration,
        ), $params));
    }
}
