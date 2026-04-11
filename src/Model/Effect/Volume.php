<?php

namespace Hunjian\AliyunImsMixcut\Model\Effect;

/**
 * 音量效果类
 *
 * 片段级音频增益控制。
 */
class Volume extends Effect
{
    /**
     * 创建音量效果。
     *
     * @param float $gain
     * @param array $params
     *
     * @return self
     */
    public static function gain($gain, array $params = array())
    {
        return new self('Volume', null, array_merge(array(
            'Gain' => $gain,
        ), $params));
    }
}
