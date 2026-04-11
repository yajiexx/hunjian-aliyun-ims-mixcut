<?php

namespace Hunjian\AliyunImsMixcut\Model;

/**
 * Class SubtitleTrackClip
 *
 * Official subtitle clip structure with FECanvas-friendly text settings.
 */
class SubtitleTrackClip extends BaseStructure
{
    /**
     * @var string
     */
    protected $type = 'Text';

    /**
     * @var string|null
     */
    protected $content;

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
     * @var string|null
     */
    protected $clipId;

    /**
     * @var string|null
     */
    protected $referenceClipId;

    /**
     * @var float|null
     */
    protected $x;

    /**
     * @var float|null
     */
    protected $y;

    /**
     * @var float|null
     */
    protected $width;

    /**
     * @var float|null
     */
    protected $height;

    /**
     * @var string|null
     */
    protected $font;

    /**
     * @var string|null
     */
    protected $fontColor;

    /**
     * @var int|null
     */
    protected $fontSize;

    /**
     * @var FontFace|null
     */
    protected $fontFace;

    /**
     * @var bool|null
     */
    protected $autoWrap;

    /**
     * @var bool|null
     */
    protected $fixedFontSize;

    /**
     * @var string|null
     */
    protected $alignment;

    /**
     * @var array
     */
    protected $subtitleEffects = array();

    /**
     * Create text subtitle clip.
     *
     * @param string     $content
     * @param float|null $timelineIn
     * @param float|null $timelineOut
     *
     * @return self
     */
    public static function text($content, $timelineIn = null, $timelineOut = null)
    {
        $clip = new self();
        $clip->setContent($content);
        $clip->setTimelineRange($timelineIn, $timelineOut);

        return $clip;
    }

    /**
     * Set subtitle content.
     *
     * @param string $content
     *
     * @return $this
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Set active range.
     *
     * @param float|null $timelineIn
     * @param float|null $timelineOut
     *
     * @return $this
     */
    public function setTimelineRange($timelineIn, $timelineOut)
    {
        $this->timelineIn = $timelineIn;
        $this->timelineOut = $timelineOut;

        return $this;
    }

    /**
     * Set duration.
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
     * Set subtitle style.
     *
     * @param string         $font
     * @param int            $fontSize
     * @param string         $fontColor
     * @param FontFace|null  $fontFace
     *
     * @return $this
     */
    public function setStyle($font, $fontSize, $fontColor, FontFace $fontFace = null)
    {
        $this->font = $font;
        $this->fontSize = (int) $fontSize;
        $this->fontColor = $fontColor;
        $this->fontFace = $fontFace;

        return $this;
    }

    /**
     * Set layout rect.
     *
     * @param float  $x
     * @param float  $y
     * @param float  $width
     * @param float  $height
     * @param string $alignment
     *
     * @return $this
     */
    public function setLayout($x, $y, $width, $height, $alignment = 'BottomCenter')
    {
        $this->x = $x;
        $this->y = $y;
        $this->width = $width;
        $this->height = $height;
        $this->alignment = $alignment;

        return $this;
    }

    /**
     * Enable auto wrap.
     *
     * @param bool $autoWrap
     *
     * @return $this
     */
    public function setAutoWrap($autoWrap)
    {
        $this->autoWrap = (bool) $autoWrap;

        return $this;
    }

    /**
     * Set fixed font size mode.
     *
     * @param bool $fixedFontSize
     *
     * @return $this
     */
    public function setFixedFontSize($fixedFontSize)
    {
        $this->fixedFontSize = (bool) $fixedFontSize;

        return $this;
    }

    /**
     * Set clip ID.
     *
     * @param string $clipId
     *
     * @return $this
     */
    public function setClipId($clipId)
    {
        $this->clipId = $clipId;

        return $this;
    }

    /**
     * Set reference clip ID.
     *
     * @param string $referenceClipId
     *
     * @return $this
     */
    public function setReferenceClipId($referenceClipId)
    {
        $this->referenceClipId = $referenceClipId;

        return $this;
    }

    /**
     * Add subtitle effect.
     *
     * @param SubtitleEffect $effect
     *
     * @return $this
     */
    public function addSubtitleEffect(SubtitleEffect $effect)
    {
        $this->subtitleEffects[] = $effect;

        return $this;
    }

    /**
     * Convert object to array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->finalize(array(
            'Type' => $this->type,
            'Content' => $this->content,
            'TimelineIn' => $this->timelineIn,
            'TimelineOut' => $this->timelineOut,
            'Duration' => $this->duration,
            'ClipId' => $this->clipId,
            'ReferenceClipId' => $this->referenceClipId,
            'X' => $this->x,
            'Y' => $this->y,
            'Width' => $this->width,
            'Height' => $this->height,
            'Font' => $this->font,
            'FontColor' => $this->fontColor,
            'FontSize' => $this->fontSize,
            'FontFace' => $this->fontFace,
            'Alignment' => $this->alignment,
            'AutoWrap' => $this->autoWrap,
            'FixedFontSize' => $this->fixedFontSize,
            'SubtitleEffects' => $this->subtitleEffects,
        ));
    }
}
