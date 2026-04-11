<?php

namespace Hunjian\AliyunImsMixcut\Model;

/**
 * 字体样式类
 *
 * 镜像官方 FontFace 结构。
 */
class FontFace extends BaseStructure
{
    /**
     * @var bool|null
     */
    protected $bold;

    /**
     * @var bool|null
     */
    protected $italic;

    /**
     * @var bool|null
     */
    protected $underline;

    /**
     * 创建字体样式对象。
     *
     * @param bool|null $bold
     * @param bool|null $italic
     * @param bool|null $underline
     */
    public function __construct($bold = null, $italic = null, $underline = null)
    {
        $this->bold = $bold;
        $this->italic = $italic;
        $this->underline = $underline;
    }

    /**
     * 粗体样式工厂方法。
     *
     * @return self
     */
    public static function bold()
    {
        return new self(true, false, false);
    }

    /**
     * 设置粗体。
     *
     * @param bool $bold
     *
     * @return $this
     */
    public function setBold($bold)
    {
        $this->bold = (bool) $bold;

        return $this;
    }

    /**
     * 设置斜体。
     *
     * @param bool $italic
     *
     * @return $this
     */
    public function setItalic($italic)
    {
        $this->italic = (bool) $italic;

        return $this;
    }

    /**
     * 设置下划线。
     *
     * @param bool $underline
     *
     * @return $this
     */
    public function setUnderline($underline)
    {
        $this->underline = (bool) $underline;

        return $this;
    }

    /**
     * 将对象转换为数组。
     *
     * @return array
     */
    public function toArray()
    {
        return $this->finalize(array(
            'Bold' => $this->bold,
            'Italic' => $this->italic,
            'Underline' => $this->underline,
        ));
    }
}
