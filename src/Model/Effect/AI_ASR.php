<?php

namespace Hunjian\AliyunImsMixcut\Model\Effect;

/**
 * AI 自动字幕生成效果类
 *
 * AI 自动字幕生成效果。
 */
class AI_ASR extends Effect
{
    /**
     * 创建 AI ASR 效果。
     *
     * @param string $language
     * @param array  $params
     *
     * @return self
     */
    public static function make($language = 'zh-CN', array $params = array())
    {
        return new self('AI_ASR', null, array_merge(array(
            'Language' => $language,
        ), $params));
    }
}
