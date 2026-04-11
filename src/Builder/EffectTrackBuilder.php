<?php

namespace Hunjian\AliyunImsMixcut\Builder;

use Hunjian\AliyunImsMixcut\Model\EffectTrack;
use Hunjian\AliyunImsMixcut\Model\EffectTrackItem;

/**
 * Class EffectTrackBuilder
 *
 * Builder for global EffectTracks.
 */
class EffectTrackBuilder
{
    /**
     * @var EffectTrack
     */
    protected $track;

    /**
     * Create builder.
     */
    public function __construct()
    {
        $this->track = new EffectTrack();
    }

    /**
     * Add a global image effect.
     *
     * @param string     $mediaURL
     * @param float      $x
     * @param float      $y
     * @param float      $width
     * @param float      $height
     * @param float|null $timelineIn
     * @param float|null $timelineOut
     * @param array      $extra
     *
     * @return $this
     */
    public function addGlobalImage($mediaURL, $x, $y, $width, $height, $timelineIn = 0.0, $timelineOut = null, array $extra = array())
    {
        $item = new EffectTrackItem('GlobalImage', null, array_merge(array(
            'MediaURL' => $mediaURL,
            'X' => $x,
            'Y' => $y,
            'Width' => $width,
            'Height' => $height,
        ), $extra));
        $item->setRange($timelineIn, $timelineOut);
        $this->track->addItem($item);

        return $this;
    }

    /**
     * Add watermark image.
     *
     * @param string     $mediaURL
     * @param float      $x
     * @param float      $y
     * @param float      $width
     * @param float      $height
     * @param float|null $timelineIn
     * @param float|null $timelineOut
     * @param array      $extra
     *
     * @return $this
     */
    public function addWatermark($mediaURL, $x, $y, $width, $height, $timelineIn = 0.0, $timelineOut = null, array $extra = array())
    {
        return $this->addGlobalImage($mediaURL, $x, $y, $width, $height, $timelineIn, $timelineOut, $extra);
    }

    /**
     * Add global filter effect.
     *
     * @param string     $subType
     * @param float|null $timelineIn
     * @param float|null $timelineOut
     * @param array      $params
     *
     * @return $this
     */
    public function addFilter($subType, $timelineIn = 0.0, $timelineOut = null, array $params = array())
    {
        $item = new EffectTrackItem('Filter', $subType, $params);
        $item->setRange($timelineIn, $timelineOut);
        $this->track->addItem($item);

        return $this;
    }

    /**
     * Add global VFX effect.
     *
     * @param string     $subType
     * @param float|null $timelineIn
     * @param float|null $timelineOut
     * @param array      $params
     *
     * @return $this
     */
    public function addVfx($subType, $timelineIn = 0.0, $timelineOut = null, array $params = array())
    {
        $item = new EffectTrackItem('VFX', $subType, $params);
        $item->setRange($timelineIn, $timelineOut);
        $this->track->addItem($item);

        return $this;
    }

    /**
     * Add custom effect track item.
     *
     * @param EffectTrackItem $item
     *
     * @return $this
     */
    public function addItem(EffectTrackItem $item)
    {
        $this->track->addItem($item);

        return $this;
    }

    /**
     * Build effect track.
     *
     * @return EffectTrack
     */
    public function build()
    {
        return $this->track;
    }
}
