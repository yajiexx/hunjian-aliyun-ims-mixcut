<?php

namespace Hunjian\AliyunImsMixcut\Model\Effect;

/**
 * 视觉特效效果类
 *
 * 片段级视觉特效效果。
 */
class VFX extends Effect
{
    /**
     * 创建视觉特效效果。
     *
     * @param string $subType
     * @param array  $params
     *
     * @return self
     */
    public static function make($subType, array $params = array())
    {
        return new self('VFX', $subType, $params);
    }
}
