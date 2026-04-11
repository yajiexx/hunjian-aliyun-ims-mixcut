<?php

namespace Hunjian\AliyunImsMixcut\Model\Effect;

use Hunjian\AliyunImsMixcut\Model\BaseStructure;

/**
 * 效果基类
 *
 * 与官方 Effects 项目结构对齐的通用片段效果对象。
 */
class Effect extends BaseStructure
{
    /**
     * @var string
     */
    protected $type;

    /**
     * @var string|null
     */
    protected $subType;

    /**
     * @var array
     */
    protected $params = array();

    /**
     * 创建通用效果对象。
     *
     * @param string      $type
     * @param string|null $subType
     * @param array       $params
     */
    public function __construct($type, $subType = null, array $params = array())
    {
        $this->type = $type;
        $this->subType = $subType;
        $this->params = $params;
    }

    /**
     * 设置参数。
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return $this
     */
    public function setParam($key, $value)
    {
        $this->params[$key] = $value;

        return $this;
    }

    /**
     * 将效果转换为数组。
     *
     * @return array
     */
    public function toArray()
    {
        return $this->finalize(array_merge(array(
            'Type' => $this->type,
            'SubType' => $this->subType,
        ), $this->params));
    }
}
