<?php

namespace Hunjian\AliyunImsMixcut\Model\Effect;

/**
 * 音频淡入淡出效果类
 *
 * 音频淡入/淡出效果。
 */
class AFade extends Effect
{
    /**
     * 创建音频淡入淡出效果。
     *
     * @param string $mode
     * @param float  $duration
     * @param array  $params
     *
     * @return self
     */
    public static function make($mode, $duration, array $params = array())
    {
        return new self('AFade', null, array_merge(array(
            'Mode' => $mode,
            'Duration' => $duration,
        ), $params));
    }
}
