<?php

namespace Hunjian\AliyunImsMixcut\Model\Effect;

/**
 * 响度标准化效果类
 *
 * 音频响度标准化效果。
 */
class ALoudNorm extends Effect
{
    /**
     * 创建响度标准化效果。
     *
     * @param float $i
     * @param float $tp
     * @param float $lra
     *
     * @return self
     */
    public static function make($i = -16.0, $tp = -1.5, $lra = 11.0)
    {
        return new self('ALoudNorm', null, array(
            'I' => $i,
            'TP' => $tp,
            'LRA' => $lra,
        ));
    }
}
