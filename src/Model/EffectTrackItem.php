<?php

namespace Hunjian\AliyunImsMixcut\Model;

/**
 * 效果轨道项目类
 *
 * 用于滤镜、视觉特效和音频均衡的全局时间线效果项目。
 */
class EffectTrackItem extends BaseStructure
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
     * @var float|null
     */
    protected $timelineIn;

    /**
     * @var float|null
     */
    protected $timelineOut;

    /**
     * @var float|null
     */
    protected $duration;

    /**
     * @var array
     */
    protected $params = array();

    /**
     * 创建全局效果项目。
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
     * 设置生效范围。
     *
     * @param float|null $timelineIn
     * @param float|null $timelineOut
     *
     * @return $this
     */
    public function setRange($timelineIn, $timelineOut)
    {
        $this->timelineIn = $timelineIn;
        $this->timelineOut = $timelineOut;

        return $this;
    }

    /**
     * 设置持续时间。
     *
     * @param float $duration
     *
     * @return $this
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;

        return $this;
    }

    /**
     * 设置自定义参数。
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
     * 将对象转换为数组。
     *
     * @return array
     */
    public function toArray()
    {
        return $this->finalize(array_merge(array(
            'Type' => $this->type,
            'SubType' => $this->subType,
            'TimelineIn' => $this->timelineIn,
            'TimelineOut' => $this->timelineOut,
            'Duration' => $this->duration,
        ), $this->params));
    }
}
