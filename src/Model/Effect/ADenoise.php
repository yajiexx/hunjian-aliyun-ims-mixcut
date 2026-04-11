<?php

namespace Hunjian\AliyunImsMixcut\Model\Effect;

/**
 * 音频降噪效果类
 *
 * 音频降噪效果。
 */
class ADenoise extends Effect
{
    /**
     * 创建音频降噪效果。
     *
     * @param string $mode
     * @param array  $params
     *
     * @return self
     */
    public static function make($mode, array $params = array())
    {
        return new self('ADenoise', null, array_merge(array(
            'Mode' => $mode,
        ), $params));
    }
}
