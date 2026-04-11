<?php

namespace Hunjian\AliyunImsMixcut\Contracts;

/**
 * 模板接口
 *
 * 本地包模板契约。与 IMS TemplateId 无关。
 */
interface TemplateInterface
{
    /**
     * 构建时间线结果负载。
     *
     * @param array $context
     *
     * @return array
     */
    public function build(array $context = array());
}
