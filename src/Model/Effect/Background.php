<?php

namespace Hunjian\AliyunImsMixcut\Model\Effect;

/**
 * 背景效果类
 *
 * 片段背景和背景模糊配置。
 */
class Background extends Effect
{
    /**
     * 创建背景模糊效果。
     *
     * @param float $radius
     * @param array $params
     *
     * @return self
     */
    public static function blur($radius, array $params = array())
    {
        return new self('Background', 'Blur', array_merge(array(
            'Radius' => $radius,
        ), $params));
    }

    /**
     * 创建纯色背景效果。
     *
     * @param string $color
     * @param array  $params
     *
     * @return self
     */
    public static function color($color, array $params = array())
    {
        return new self('Background', 'Color', array_merge(array(
            'Color' => $color,
        ), $params));
    }
}
