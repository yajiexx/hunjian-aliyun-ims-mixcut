<?php

namespace Hunjian\AliyunImsMixcut\Model\Effect;

/**
 * 裁剪效果类
 *
 * 裁剪区域效果。
 */
class Crop extends Effect
{
    /**
     * 创建裁剪效果。
     *
     * @param float $x
     * @param float $y
     * @param float $width
     * @param float $height
     * @param array $params
     *
     * @return self
     */
    public static function rect($x, $y, $width, $height, array $params = array())
    {
        return new self('Crop', null, array_merge(array(
            'X' => $x,
            'Y' => $y,
            'Width' => $width,
            'Height' => $height,
        ), $params));
    }
}
