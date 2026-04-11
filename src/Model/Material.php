<?php

namespace Hunjian\AliyunImsMixcut\Model;

/**
 * Class Material
 *
 * Business-friendly material DTO for videos, images and audios.
 */
class Material extends BaseStructure
{
    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $url;

    /**
     * @var float|null
     */
    protected $duration;

    /**
     * @var float|null
     */
    protected $sourceDuration;

    /**
     * @var string|null
     */
    protected $subtitle;

    /**
     * @var string|null
     */
    protected $adaptMode;

    /**
     * Create DTO.
     *
     * @param string $type
     * @param string $url
     */
    public function __construct($type, $url)
    {
        $this->type = $type;
        $this->url = $url;
    }

    /**
     * Create video material.
     *
     * @param string     $url
     * @param float|null $sourceDuration
     *
     * @return self
     */
    public static function video($url, $sourceDuration = null)
    {
        return (new self('video', $url))->setSourceDuration($sourceDuration);
    }

    /**
     * Create image material.
     *
     * @param string     $url
     * @param float|null $duration
     *
     * @return self
     */
    public static function image($url, $duration = null)
    {
        return (new self('image', $url))->setDuration($duration);
    }

    /**
     * Create audio material.
     *
     * @param string     $url
     * @param float|null $sourceDuration
     *
     * @return self
     */
    public static function audio($url, $sourceDuration = null)
    {
        return (new self('audio', $url))->setSourceDuration($sourceDuration);
    }

    /**
     * Set material duration.
     *
     * @param float|null $duration
     *
     * @return $this
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;

        return $this;
    }

    /**
     * Set source duration.
     *
     * @param float|null $sourceDuration
     *
     * @return $this
     */
    public function setSourceDuration($sourceDuration)
    {
        $this->sourceDuration = $sourceDuration;

        return $this;
    }

    /**
     * Set default subtitle copy.
     *
     * @param string $subtitle
     *
     * @return $this
     */
    public function setSubtitle($subtitle)
    {
        $this->subtitle = $subtitle;

        return $this;
    }

    /**
     * Set adapt mode.
     *
     * @param string $adaptMode
     *
     * @return $this
     */
    public function setAdaptMode($adaptMode)
    {
        $this->adaptMode = $adaptMode;

        return $this;
    }

    /**
     * Get type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Convert to template pool array.
     *
     * @return array
     */
    public function toTemplateItem()
    {
        return array(
            'type' => $this->type,
            'url' => $this->url,
            'duration' => $this->duration,
            'sourceDuration' => $this->sourceDuration,
            'subtitle' => $this->subtitle,
            'adaptMode' => $this->adaptMode,
        );
    }

    /**
     * Convert object to array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->finalize(array(
            'type' => $this->type,
            'url' => $this->url,
            'duration' => $this->duration,
            'sourceDuration' => $this->sourceDuration,
            'subtitle' => $this->subtitle,
            'adaptMode' => $this->adaptMode,
        ));
    }
}
