<?php

namespace Hunjian\AliyunImsMixcut\Model;

/**
 * Class ThemeConfig
 *
 * Theme/activity-level defaults for templates and batch task generation.
 */
class ThemeConfig extends BaseStructure
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string|null
     */
    protected $voice;

    /**
     * @var string|null
     */
    protected $bgm;

    /**
     * @var array
     */
    protected $bgmPool = array();

    /**
     * @var string|null
     */
    protected $watermark;

    /**
     * @var string|null
     */
    protected $globalFilter;

    /**
     * @var string|null
     */
    protected $globalVfx;

    /**
     * @var array
     */
    protected $subtitleStyle = array();

    /**
     * @var array
     */
    protected $highlight = array();

    /**
     * @var string|null
     */
    protected $outputPattern;

    /**
     * @var array
     */
    protected $defaults = array();

    /**
     * Create config.
     *
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Set voice.
     *
     * @param string $voice
     *
     * @return $this
     */
    public function setVoice($voice)
    {
        $this->voice = $voice;

        return $this;
    }

    /**
     * Set default single BGM.
     *
     * @param string $bgm
     *
     * @return $this
     */
    public function setBgm($bgm)
    {
        $this->bgm = $bgm;

        return $this;
    }

    /**
     * Set BGM pool.
     *
     * @param array $bgmPool
     *
     * @return $this
     */
    public function setBgmPool(array $bgmPool)
    {
        $this->bgmPool = $bgmPool;

        return $this;
    }

    /**
     * Set watermark.
     *
     * @param string $watermark
     *
     * @return $this
     */
    public function setWatermark($watermark)
    {
        $this->watermark = $watermark;

        return $this;
    }

    /**
     * Set global filter.
     *
     * @param string $globalFilter
     *
     * @return $this
     */
    public function setGlobalFilter($globalFilter)
    {
        $this->globalFilter = $globalFilter;

        return $this;
    }

    /**
     * Set global VFX.
     *
     * @param string $globalVfx
     *
     * @return $this
     */
    public function setGlobalVfx($globalVfx)
    {
        $this->globalVfx = $globalVfx;

        return $this;
    }

    /**
     * Set subtitle style.
     *
     * @param array $subtitleStyle
     *
     * @return $this
     */
    public function setSubtitleStyle(array $subtitleStyle)
    {
        $this->subtitleStyle = $subtitleStyle;

        return $this;
    }

    /**
     * Set highlight config.
     *
     * @param array $highlight
     *
     * @return $this
     */
    public function setHighlight(array $highlight)
    {
        $this->highlight = $highlight;

        return $this;
    }

    /**
     * Set output pattern.
     *
     * @param string $outputPattern
     *
     * @return $this
     */
    public function setOutputPattern($outputPattern)
    {
        $this->outputPattern = $outputPattern;

        return $this;
    }

    /**
     * Set additional context defaults.
     *
     * @param array $defaults
     *
     * @return $this
     */
    public function setDefaults(array $defaults)
    {
        $this->defaults = array_merge($this->defaults, $defaults);

        return $this;
    }

    /**
     * Get voice.
     *
     * @return string|null
     */
    public function getVoice()
    {
        return $this->voice;
    }

    /**
     * Get theme name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get single BGM.
     *
     * @return string|null
     */
    public function getBgm()
    {
        return $this->bgm;
    }

    /**
     * Get BGM pool.
     *
     * @return array
     */
    public function getBgmPool()
    {
        return $this->bgmPool;
    }

    /**
     * Get watermark.
     *
     * @return string|null
     */
    public function getWatermark()
    {
        return $this->watermark;
    }

    /**
     * Get global filter.
     *
     * @return string|null
     */
    public function getGlobalFilter()
    {
        return $this->globalFilter;
    }

    /**
     * Get global VFX.
     *
     * @return string|null
     */
    public function getGlobalVfx()
    {
        return $this->globalVfx;
    }

    /**
     * Get subtitle style.
     *
     * @return array
     */
    public function getSubtitleStyle()
    {
        return $this->subtitleStyle;
    }

    /**
     * Get highlight config.
     *
     * @return array
     */
    public function getHighlight()
    {
        return $this->highlight;
    }

    /**
     * Get output pattern.
     *
     * @return string|null
     */
    public function getOutputPattern()
    {
        return $this->outputPattern;
    }

    /**
     * Resolve output URL for task number.
     *
     * @param int $n
     *
     * @return string|null
     */
    public function resolveOutputMediaUrl($n)
    {
        if ($this->outputPattern === null) {
            return null;
        }

        return str_replace(
            array('{theme}', '{n}'),
            array($this->name, (string) $n),
            $this->outputPattern
        );
    }

    /**
     * Apply theme defaults into context without overwriting explicit values.
     *
     * @param array $context
     *
     * @return array
     */
    public function applyToContext(array $context)
    {
        $defaults = array_merge(array(
            'theme' => $this,
        ), $this->defaults);

        foreach ($defaults as $key => $value) {
            if (!array_key_exists($key, $context)) {
                $context[$key] = $value;
            }
        }

        return $context;
    }

    /**
     * Convert to array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->finalize(array(
            'name' => $this->name,
            'voice' => $this->voice,
            'bgm' => $this->bgm,
            'bgmPool' => $this->bgmPool,
            'watermark' => $this->watermark,
            'globalFilter' => $this->globalFilter,
            'globalVfx' => $this->globalVfx,
            'subtitleStyle' => $this->subtitleStyle,
            'highlight' => $this->highlight,
            'outputPattern' => $this->outputPattern,
            'defaults' => $this->defaults,
        ));
    }
}
