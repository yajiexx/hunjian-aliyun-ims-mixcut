<?php

namespace Hunjian\AliyunImsMixcut\Model;

/**
 * 字幕效果类
 *
 * 支持字幕框/描边/阴影样式层。
 */
class SubtitleEffect extends BaseStructure
{
    /**
     * @var string
     */
    protected $type;

    /**
     * @var array
     */
    protected $params = array();

    /**
     * 创建字幕效果对象。
     *
     * @param string $type
     * @param array  $params
     */
    public function __construct($type, array $params = array())
    {
        $this->type = $type;
        $this->params = $params;
    }

    /**
     * 创建字幕背景框效果。
     *
     * @param string $color
     * @param float  $opacity
     * @param int    $bord
     *
     * @return self
     */
    public static function box($color, $opacity, $bord)
    {
        return new self('Box', array(
            'Color' => $color,
            'Opacity' => $opacity,
            'Bord' => $bord,
        ));
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
        ), $this->params));
    }
}
