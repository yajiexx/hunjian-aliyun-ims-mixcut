<?php

namespace Hunjian\AliyunImsMixcut\Model\Effect;

/**
 * 音频均衡器效果类
 *
 * 音频均衡器效果。
 */
class AEqualize extends Effect
{
    /**
     * 创建均衡器效果。
     *
     * @param int   $frequency
     * @param float $width
     * @param float $gain
     *
     * @return self
     */
    public static function make($frequency, $width, $gain)
    {
        return new self('AEqualize', null, array(
            'Frequency' => $frequency,
            'Width' => $width,
            'Gain' => $gain,
        ));
    }
}
