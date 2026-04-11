<?php

namespace Hunjian\AliyunImsMixcut\Builder;

use Hunjian\AliyunImsMixcut\Model\FontFace;
use Hunjian\AliyunImsMixcut\Model\SubtitleEffect;
use Hunjian\AliyunImsMixcut\Model\SubtitleTrack;
use Hunjian\AliyunImsMixcut\Model\SubtitleTrackClip;

/**
 * Class SubtitleBuilder
 *
 * Builder for subtitle styles and batches of subtitle clips.
 */
class SubtitleBuilder
{
    /**
     * @var string
     */
    protected $font = 'Alibaba PuHuiTi 2.0';

    /**
     * @var int
     */
    protected $fontSize = 48;

    /**
     * @var string
     */
    protected $fontColor = '#FFFFFF';

    /**
     * @var FontFace|null
     */
    protected $fontFace;

    /**
     * @var bool
     */
    protected $autoWrap = true;

    /**
     * @var bool
     */
    protected $fixedFontSize = true;

    /**
     * @var array
     */
    protected $layout = array(
        'x' => 80,
        'y' => 1460,
        'width' => 920,
        'height' => 220,
        'alignment' => 'BottomCenter',
    );

    /**
     * @var array
     */
    protected $subtitleEffects = array();

    /**
     * @var array
     */
    protected $extraFields = array();

    /**
     * Set font style.
     *
     * @param string        $font
     * @param int           $fontSize
     * @param string        $fontColor
     * @param FontFace|null $fontFace
     *
     * @return $this
     */
    public function style($font, $fontSize, $fontColor, FontFace $fontFace = null)
    {
        $this->font = $font;
        $this->fontSize = (int) $fontSize;
        $this->fontColor = $fontColor;
        $this->fontFace = $fontFace;

        return $this;
    }

    /**
     * Set subtitle layout.
     *
     * @param float  $x
     * @param float  $y
     * @param float  $width
     * @param float  $height
     * @param string $alignment
     *
     * @return $this
     */
    public function layout($x, $y, $width, $height, $alignment = 'BottomCenter')
    {
        $this->layout = array(
            'x' => $x,
            'y' => $y,
            'width' => $width,
            'height' => $height,
            'alignment' => $alignment,
        );

        return $this;
    }

    /**
     * Enable or disable AutoWrap.
     *
     * @param bool $autoWrap
     *
     * @return $this
     */
    public function autoWrap($autoWrap = true)
    {
        $this->autoWrap = (bool) $autoWrap;

        return $this;
    }

    /**
     * Enable or disable FixedFontSize.
     *
     * @param bool $fixedFontSize
     *
     * @return $this
     */
    public function fixedFontSize($fixedFontSize = true)
    {
        $this->fixedFontSize = (bool) $fixedFontSize;

        return $this;
    }

    /**
     * Add background box effect.
     *
     * @param string $color
     * @param float  $opacity
     * @param int    $bord
     *
     * @return $this
     */
    public function box($color = '#000000', $opacity = 0.35, $bord = 20)
    {
        $this->subtitleEffects[] = SubtitleEffect::box($color, $opacity, $bord);

        return $this;
    }

    /**
     * Add outline effect.
     *
     * @param string $color
     * @param int    $bord
     *
     * @return $this
     */
    public function outline($color = '#000000', $bord = 4)
    {
        $this->subtitleEffects[] = new SubtitleEffect('Outline', array(
            'Color' => $color,
            'Bord' => $bord,
        ));

        return $this;
    }

    /**
     * Add shadow effect.
     *
     * @param string $color
     * @param int    $offsetX
     * @param int    $offsetY
     *
     * @return $this
     */
    public function shadow($color = '#000000', $offsetX = 3, $offsetY = 3)
    {
        $this->subtitleEffects[] = new SubtitleEffect('Shadow', array(
            'Color' => $color,
            'OffsetX' => $offsetX,
            'OffsetY' => $offsetY,
        ));

        return $this;
    }

    /**
     * Set extra subtitle fields.
     *
     * @param array $fields
     *
     * @return $this
     */
    public function extra(array $fields)
    {
        $this->extraFields = array_merge($this->extraFields, $fields);

        return $this;
    }

    /**
     * Build a styled subtitle clip.
     *
     * @param string      $content
     * @param float       $timelineIn
     * @param float       $timelineOut
     * @param string|null $referenceClipId
     *
     * @return SubtitleTrackClip
     */
    public function make($content, $timelineIn, $timelineOut, $referenceClipId = null)
    {
        $clip = SubtitleTrackClip::text($content, $timelineIn, $timelineOut)
            ->setStyle($this->font, $this->fontSize, $this->fontColor, $this->fontFace)
            ->setLayout(
                $this->layout['x'],
                $this->layout['y'],
                $this->layout['width'],
                $this->layout['height'],
                $this->layout['alignment']
            )
            ->setAutoWrap($this->autoWrap)
            ->setFixedFontSize($this->fixedFontSize);

        if ($referenceClipId) {
            $clip->setReferenceClipId($referenceClipId);
        }

        foreach ($this->subtitleEffects as $effect) {
            $clip->addSubtitleEffect($effect);
        }

        $clip->withExtraFields($this->extraFields);

        return $clip;
    }

    /**
     * Build a subtitle track from segment arrays.
     *
     * @param array $segments
     *
     * @return SubtitleTrack
     */
    public function buildTrack(array $segments)
    {
        $track = new SubtitleTrack();

        foreach ($segments as $segment) {
            $track->addClip($this->make(
                $segment['text'],
                $segment['start'],
                $segment['end'],
                isset($segment['referenceClipId']) ? $segment['referenceClipId'] : null
            ));
        }

        return $track;
    }
}
