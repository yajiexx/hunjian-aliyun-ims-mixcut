<?php

namespace Hunjian\AliyunImsMixcut\Model;

/**
 * 视频轨道类
 *
 * 官方视频轨道结构。
 */
class VideoTrack extends BaseStructure
{
    /**
     * @var string|null
     */
    protected $type;

    /**
     * @var bool|null
     */
    protected $mainTrack;

    /**
     * @var string|null
     */
    protected $trackShortenMode;

    /**
     * @var string|null
     */
    protected $trackExpandMode;

    /**
     * @var array
     */
    protected $clips = array();

    /**
     * 设置轨道类型。
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
     * 将轨道标记为主轨道。
     *
     * @param bool $mainTrack
     *
     * @return $this
     */
    public function setMainTrack($mainTrack)
    {
        $this->mainTrack = (bool) $mainTrack;

        return $this;
    }

    /**
     * 设置轨道缩短模式。
     *
     * @param string $mode
     *
     * @return $this
     */
    public function setTrackShortenMode($mode)
    {
        $this->trackShortenMode = $mode;

        return $this;
    }

    /**
     * 设置轨道扩展模式。
     *
     * @param string $mode
     *
     * @return $this
     */
    public function setTrackExpandMode($mode)
    {
        $this->trackExpandMode = $mode;

        return $this;
    }

    /**
     * 向视频轨道添加片段。
     *
     * @param VideoTrackClip $clip
     *
     * @return $this
     */
    public function addClip(VideoTrackClip $clip)
    {
        $this->clips[] = $clip;

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
            'MainTrack' => $this->mainTrack,
            'TrackShortenMode' => $this->trackShortenMode,
            'TrackExpandMode' => $this->trackExpandMode,
            'VideoTrackClips' => $this->clips,
        ));
    }
}
