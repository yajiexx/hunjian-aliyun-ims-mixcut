<?php

namespace Hunjian\AliyunImsMixcut\Model\Effect;

/**
 * 肯本效果类
 *
 * 图像平移和缩放效果。
 */
class KenBurns extends Effect
{
    /**
     * 创建肯本效果。
     *
     * @param string $from
     * @param string $to
     * @param array  $params
     *
     * @return self
     */
    public static function make($from, $to, array $params = array())
    {
        return new self('KenBurns', null, array_merge(array(
            'From' => $from,
            'To' => $to,
        ), $params));
    }
}
