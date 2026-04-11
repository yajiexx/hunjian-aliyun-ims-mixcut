<?php

namespace Hunjian\AliyunImsMixcut\Model;

use Hunjian\AliyunImsMixcut\Model\Effect\Effect;

/**
 * 视频轨道片段类
 *
 * 带有片段级效果的官方视频片段结构。
 */
class VideoTrackClip extends BaseStructure
{
    /**
     * @var string|null
     */
    protected $type = 'Video';

    /**
     * @var string|null
     */
    protected $mediaURL;

    /**
     * @var string|null
     */
    protected $mediaId;

    /**
     * @var float|null
     */
    protected $in;

    /**
     * @var float|null
     */
    protected $out;

    /**
     * @var float|null
     */
    protected $duration;

    /**
     * @var float|null
     */
    protected $timelineIn;

    /**
     * @var float|null
     */
    protected $timelineOut;

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
    protected $adaptMode;

    /**
     * @var int|null
     */
    protected $zOrder;

    /**
     * @var array
     */
    protected $effects = array();

    /**
     * 从媒体 URL 创建视频片段。
     *
     * @param string $mediaURL
     *
     * @return self
     */
    public static function fromMediaUrl($mediaURL)
    {
        $clip = new self();
        $clip->setMediaURL($mediaURL);

        return $clip;
    }

    /**
     * 从媒体 URL 创建图片片段。
     *
     * @param string $mediaURL
     * @param float  $duration
     *
     * @return self
     */
    public static function image($mediaURL, $duration)
    {
        $clip = new self();
        $clip->setType('Image');
        $clip->setMediaURL($mediaURL);
        $clip->setDuration($duration);

        return $clip;
    }

    /**
     * 设置片段类型。
     *
     * @param string $type
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * 设置媒体 URL。
     *
     * @param string $mediaURL
     *
     * @return $this
     */
    public function setMediaURL($mediaURL)
    {
        $this->mediaURL = $mediaURL;

        return $this;
    }

    /**
     * 设置媒体 ID。
     *
     * @param string $mediaId
     *
     * @return $this
     */
    public function setMediaId($mediaId)
    {
        $this->mediaId = $mediaId;

        return $this;
    }

    /**
     * 设置源裁剪范围。
     *
     * @param float|null $in
     * @param float|null $out
     *
     * @return $this
     */
    public function setSourceRange($in, $out)
    {
        $this->in = $in;
        $this->out = $out;

        return $this;
    }

    /**
     * 设置时间线范围。
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
     * 设置片段 ID。
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
     * 设置参考片段 ID。
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
     * 设置布局矩形。
     *
     * @param float  $x
     * @param float  $y
     * @param float  $width
     * @param float  $height
     * @param string $adaptMode
     *
     * @return $this
     */
    public function setLayout($x, $y, $width, $height, $adaptMode = 'Cover')
    {
        $this->x = $x;
        $this->y = $y;
        $this->width = $width;
        $this->height = $height;
        $this->adaptMode = $adaptMode;

        return $this;
    }

    /**
     * 设置 Z 轴顺序。
     *
     * @param int $zOrder
     *
     * @return $this
     */
    public function setZOrder($zOrder)
    {
        $this->zOrder = (int) $zOrder;

        return $this;
    }

    /**
     * 添加片段级效果。
     *
     * @param Effect $effect
     *
     * @return $this
     */
    public function addEffect(Effect $effect)
    {
        $this->effects[] = $effect;

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
            'Type' => $this->type,
            'MediaURL' => $this->mediaURL,
            'MediaId' => $this->mediaId,
            'In' => $this->in,
            'Out' => $this->out,
            'Duration' => $this->duration,
            'TimelineIn' => $this->timelineIn,
            'TimelineOut' => $this->timelineOut,
            'ClipId' => $this->clipId,
            'ReferenceClipId' => $this->referenceClipId,
            'X' => $this->x,
            'Y' => $this->y,
            'Width' => $this->width,
            'Height' => $this->height,
            'AdaptMode' => $this->adaptMode,
            'ZOrder' => $this->zOrder,
            'Effects' => $this->effects,
        ));
    }
}
